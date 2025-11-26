# backend/python/forecast_sales.py
import os
import json
import pandas as pd
import numpy as np
import lightgbm as lgb
from sklearn.model_selection import train_test_split
from sklearn.metrics import mean_squared_error, mean_absolute_error
from datetime import timedelta
from sqlalchemy import create_engine, text
from flask import Flask, jsonify

# ---- Use PG* env vars (same as PHP) ----
DB_HOST = os.getenv("PGHOST", "")
DB_PORT = os.getenv("PGPORT", "5432")
DB_NAME = os.getenv("PGDATABASE", "")
DB_USER = os.getenv("PGUSER", "")
DB_PASS = os.getenv("PGPASSWORD", "")

if not all([DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS]):
    raise RuntimeError("Missing required PG* environment variables")

DB_URL = f"postgresql+psycopg2://{DB_USER}:{DB_PASS}@{DB_HOST}:{DB_PORT}/{DB_NAME}"
engine = create_engine(DB_URL, pool_pre_ping=True, pool_recycle=1800)

# ---- Constants ----
FORECAST_HORIZON_DAYS = 7
MIN_HISTORY_POINTS = 7
MODEL_NAME = "LightGBM"

TABLE_INVENTORY = "inventory"
TABLE_SALES_ITEMS = "sales_items"
TABLE_SALES = "sales"

# ---- Data access ----
def fetch_inventory() -> pd.DataFrame:
    df = pd.read_sql(
        f"SELECT product_id, name, qty, threshold FROM {TABLE_INVENTORY} ORDER BY product_id ASC;",
        engine
    )
    return df[df["product_id"].astype(str) != "product_id"].copy()

def fetch_product_sales_series(product_id: int) -> pd.DataFrame:
    sql = """
        SELECT DATE(created_at) AS date, COALESCE(SUM(qty), 0) AS qty_sold
        FROM sales_items
        WHERE product_id = :pid
        GROUP BY DATE(created_at)
        ORDER BY DATE(created_at) ASC;
    """
    df = pd.read_sql(text(sql), engine, params={"pid": int(product_id)})
    if df.empty:
        return df
    df["date"] = pd.to_datetime(df["date"])
    return df.set_index("date").asfreq("D", fill_value=0).reset_index()

def fetch_actual_sales_series() -> pd.DataFrame:
    sql = """
        SELECT DATE(created_at) AS date, COALESCE(SUM(total_amount), 0) AS actual_sales
        FROM sales
        GROUP BY DATE(created_at)
        ORDER BY DATE(created_at) ASC;
    """
    df = pd.read_sql(sql, engine)
    df["date"] = pd.to_datetime(df["date"])
    return df

def fetch_avg_prices() -> dict:
    sql = """
        SELECT product_id, AVG(price) AS avg_price
        FROM sales_items
        WHERE price IS NOT NULL AND price > 0
        GROUP BY product_id;
    """
    df = pd.read_sql(sql, engine)
    return {int(row["product_id"]): float(row["avg_price"]) for _, row in df.iterrows()}

# ---- Features & modeling ----
def build_features(df: pd.DataFrame) -> pd.DataFrame:
    df = df.copy()
    df["day"] = df["date"].dt.day
    df["month"] = df["date"].dt.month
    df["weekday"] = df["date"].dt.weekday
    df["week"] = df["date"].dt.isocalendar().week.astype(int)
    df["lag1"] = df["qty_sold"].shift(1)
    df["lag7"] = df["qty_sold"].shift(7)
    df["roll3"] = df["qty_sold"].rolling(3).mean()
    df["roll7"] = df["qty_sold"].rolling(7).mean()
    return df.dropna().reset_index(drop=True)

def train_lightgbm(X_train, y_train, X_test, y_test):
    if X_train.empty or y_train.nunique() <= 1:
        return None, 0.0
    model = lgb.LGBMRegressor(objective="regression", n_estimators=300, learning_rate=0.05, random_state=42)
    model.fit(X_train, y_train, eval_set=[(X_test, y_test)], eval_metric="rmse")
    y_pred = model.predict(X_test)
    rmse = float(np.sqrt(mean_squared_error(y_test, y_pred)))
    return model, rmse

def forecast_next_days(df_series: pd.DataFrame, model, horizon=FORECAST_HORIZON_DAYS):
    history = df_series.copy().sort_values("date").reset_index(drop=True)
    outputs = []
    last_date = history["date"].iloc[-1]
    last_values = history["qty_sold"].tolist()

    for i in range(1, horizon + 1):
        next_date = last_date + timedelta(days=i)
        lag1 = last_values[-1]
        lag7 = last_values[-7] if len(last_values) >= 7 else lag1
        roll3 = float(np.mean(last_values[-3:])) if len(last_values) >= 3 else float(np.mean(last_values))
        roll7 = float(np.mean(last_values[-7:])) if len(last_values) >= 7 else float(np.mean(last_values))

        feat = pd.DataFrame([{
            "day": next_date.day,
            "month": next_date.month,
            "weekday": next_date.weekday(),
            "week": int(next_date.isocalendar().week),
            "lag1": lag1,
            "lag7": lag7,
            "roll3": roll3,
            "roll7": roll7
        }])

        if model:
            yhat = max(0.0, float(model.predict(feat)[0]))
        else:
            yhat = float(np.mean(last_values))  # fallback
        outputs.append((next_date.date(), yhat))
        last_values.append(yhat)

    return outputs

# ---- Orchestration ----
def run_pipeline() -> dict:
    inventory_df = fetch_inventory()
    avg_prices = fetch_avg_prices()
    daily_totals_pesos = {}
    processed_products = 0
    skipped_products = []

    for _, row in inventory_df.iterrows():
        try:
            pid = int(row["product_id"])
        except (ValueError, TypeError):
            continue

        name = str(row.get("name", ""))
        series = fetch_product_sales_series(pid)
        if series.empty or len(series) < MIN_HISTORY_POINTS:
            skipped_products.append({"product_id": pid, "name": name, "reason": "insufficient history"})
            continue

        feat_df = build_features(series)
        X = feat_df[["day", "month", "weekday", "week", "lag1", "lag7", "roll3", "roll7"]]
        y = feat_df["qty_sold"]

        X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, shuffle=False)
        model, rmse = train_lightgbm(X_train, y_train, X_test, y_test)

        forecasts = forecast_next_days(series, model, horizon=FORECAST_HORIZON_DAYS)

        price = avg_prices.get(pid, 0.0)
        for fdate, qty in forecasts:
            daily_totals_pesos[fdate] = daily_totals_pesos.get(fdate, 0.0) + (qty * price)

        processed_products += 1

    # Metrics
    actual_df = fetch_actual_sales_series()
    forecast_df = pd.DataFrame(list(daily_totals_pesos.items()), columns=["date", "forecast_amount"])
    forecast_df["date"] = pd.to_datetime(forecast_df["date"])
    actual_df["date"] = pd.to_datetime(actual_df["date"])

    merged = pd.merge(actual_df, forecast_df, on="date", how="inner")
    if merged.empty:
        rmse = mae = mape = 0.0
    else:
        rmse = float(np.sqrt(mean_squared_error(merged["actual_sales"], merged["forecast_amount"])))
        mae = float(mean_absolute_error(merged["actual_sales"], merged["forecast_amount"]))
        safe_actual = merged["actual_sales"].replace(0, np.nan)
        mape = float(np.nanmean(np.abs((safe_actual - merged["forecast_amount"]) / safe_actual)) * 100)

    return {
        "processed_products": processed_products,
        "skipped_products": skipped_products,
        "metrics": {"rmse": rmse, "mae": mae, "mape": mape},
        "horizon": FORECAST_HORIZON_DAYS,
        "forecast": forecast_df.to_dict(orient="records")
    }

# ---- Flask App ----
app = Flask(__name__)

@app.route("/health", methods=["GET"])
def health():
    try:
        with engine.connect() as conn:
            conn.execute(text("SELECT 1"))
        return jsonify({"status": "ok"}), 200
    except Exception as e:
        return jsonify({"status": "error", "message": str(e)}), 500

@app.route("/forecast", methods=["GET"])
def forecast_endpoint():
    try:
        result = run_pipeline()
        return jsonify({"status": "success", **result}), 200
    except Exception as e:
        return jsonify({"status": "error", "message": str(e)}), 500

if __name__ == "__main__":
    try:
        result = run_pipeline()
        print(json.dumps({"status": "success", **result}, ensure_ascii=False))
    except Exception as e:
        print(json.dumps({"status": "error", "message": str(e)}))
