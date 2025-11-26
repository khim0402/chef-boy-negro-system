# backend/python/forecast_sales.py
import os
import json
import pandas as pd
import numpy as np
import lightgbm as lgb
from sklearn.model_selection import train_test_split
from sklearn.metrics import mean_squared_error, mean_absolute_error
from datetime import timedelta, date
from sqlalchemy import create_engine, text
from flask import Flask, jsonify, request

# ---- Use PG* env vars (aligned with PHP) ----
DB_HOST = os.getenv("PGHOST", "")
DB_PORT = os.getenv("PGPORT", "5432")
DB_NAME = os.getenv("PGDATABASE", "")
DB_USER = os.getenv("PGUSER", "")
DB_PASS = os.getenv("PGPASSWORD", "")

if not all([DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS]):
    raise RuntimeError("Missing required PG* environment variables")

DB_URL = f"postgresql+psycopg2://{DB_USER}:{DB_PASS}@{DB_HOST}:{DB_PORT}/{DB_NAME}"
engine = create_engine(DB_URL, pool_pre_ping=True, pool_recycle=1800)

# ---- Limits tuned for Render free tier ----
FORECAST_HORIZON_DAYS = 7
MIN_HISTORY_POINTS = 7
MAX_PRODUCTS_PER_RUN = 10  # batch size per manual run

MODEL_NAME = "LightGBM"

TABLE_INVENTORY = "inventory"
TABLE_SALES_ITEMS = "sales_items"
TABLE_SALES = "sales"
TABLE_PRODUCT_FORECAST = "product_forecast"
TABLE_SALES_FORECAST = "sales_forecast"
TABLE_FORECAST_METRICS = "forecast_metrics"

# ---- Data access ----
def fetch_inventory() -> pd.DataFrame:
    df = pd.read_sql(
        f"SELECT product_id, name, qty, threshold FROM {TABLE_INVENTORY} ORDER BY product_id ASC;",
        engine
    )
    # Filter out any header-like rows if present
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
    # Daily frequency with zero-filling to stabilize features
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
    # Lightweight parameters for Render free tier
    if X_train.empty or y_train.nunique() <= 1:
        return None, 0.0
    model = lgb.LGBMRegressor(
        objective="regression",
        n_estimators=50,     # fewer trees
        num_leaves=15,       # smaller leaves
        max_depth=3,         # shallow trees
        learning_rate=0.1,
        random_state=42
    )
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

        if model is not None:
            yhat = max(0.0, float(model.predict(feat)[0]))
        else:
            yhat = float(np.mean(last_values))  # fallback
        outputs.append((next_date.date(), yhat))
        last_values.append(yhat)

    return outputs

# ---- Persistence (cache forecasts) ----
def write_sales_forecast(forecast_df: pd.DataFrame):
    """Replace old forecasts with the latest run."""
    if forecast_df.empty:
        return
    with engine.begin() as conn:
        # Delete overlapping horizon before inserting new values
        conn.execute(text(f"DELETE FROM {TABLE_SALES_FORECAST} WHERE date >= :start_date"), {
            "start_date": forecast_df["date"].min().date()
        })
        rows = [
            {"date": pd.to_datetime(r["date"]).date(), "forecast_amount": float(r["forecast_amount"])}
            for _, r in forecast_df.iterrows()
        ]
        conn.execute(
            text(f"INSERT INTO {TABLE_SALES_FORECAST} (date, forecast_amount) VALUES (:date, :forecast_amount)"),
            rows
        )

def write_product_forecast(product_id: int, forecasts: list):
    """Store per-product forecasts (date, qty)."""
    if not forecasts:
        return
    with engine.begin() as conn:
        # Optional: clear future horizon for product
        conn.execute(text(f"DELETE FROM {TABLE_PRODUCT_FORECAST} WHERE product_id = :pid AND date >= :start_date"), {
            "pid": product_id,
            "start_date": min(f[0] for f in forecasts)
        })
        rows = [{"product_id": product_id, "date": f[0], "qty": float(f[1])} for f in forecasts]
        conn.execute(
            text(f"INSERT INTO {TABLE_PRODUCT_FORECAST} (product_id, date, qty) VALUES (:product_id, :date, :qty)"),
            rows
        )

def write_forecast_metrics(rmse: float, mae: float, mape: float, processed_products: int):
    with engine.begin() as conn:
        conn.execute(
            text(f"""
                INSERT INTO {TABLE_FORECAST_METRICS} (run_at, model_name, rmse, mae, mape, processed_products)
                VALUES (NOW(), :model_name, :rmse, :mae, :mape, :processed)
            """),
            {"model_name": MODEL_NAME, "rmse": rmse, "mae": mae, "mape": mape, "processed": processed_products}
        )

# ---- Orchestration ----
def run_pipeline(inventory_slice: pd.DataFrame | None = None) -> dict:
    inventory_df = inventory_slice if inventory_slice is not None else fetch_inventory()
    # Enforce batch limit for safety
    inventory_df = inventory_df.head(MAX_PRODUCTS_PER_RUN)

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

        # Additional safety: avoid training on tiny sets
        if len(X) < 20 or y.nunique() <= 1:
            model, rmse = None, 0.0
        else:
            X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, shuffle=False)
            model, rmse = train_lightgbm(X_train, y_train, X_test, y_test)

        # Forecast (with fallback)
        if model is None:
            forecasts = [(date.today() + timedelta(days=i), float(y.mean()))
                         for i in range(1, FORECAST_HORIZON_DAYS + 1)]
        else:
            forecasts = forecast_next_days(series, model, horizon=FORECAST_HORIZON_DAYS)

        # Cache per-product forecasts
        write_product_forecast(pid, forecasts)

        # Aggregate pesos using avg price
        price = avg_prices.get(pid, 0.0)
        for fdate, qty in forecasts:
            daily_totals_pesos[fdate] = daily_totals_pesos.get(fdate, 0.0) + (qty * price)

        processed_products += 1

    # Metrics
    actual_df = fetch_actual_sales_series()
    forecast_df = pd.DataFrame(list(daily_totals_pesos.items()), columns=["date", "forecast_amount"])
    if not forecast_df.empty:
        forecast_df["date"] = pd.to_datetime(forecast_df["date"])
    actual_df["date"] = pd.to_datetime(actual_df["date"])

    merged = pd.merge(actual_df, forecast_df, on="date", how="inner")
    if merged.empty or merged["actual_sales"].sum() == 0:
        rmse = mae = mape = 0.0
    else:
        rmse = float(np.sqrt(mean_squared_error(merged["actual_sales"], merged["forecast_amount"])))
        mae = float(mean_absolute_error(merged["actual_sales"], merged["forecast_amount"]))
        safe_actual = merged["actual_sales"].replace(0, np.nan)
        mape = float(np.nanmean(np.abs((safe_actual - merged["forecast_amount"]) / safe_actual)) * 100)

    # Cache sales forecasts and metrics
    if not forecast_df.empty:
        write_sales_forecast(forecast_df)
    write_forecast_metrics(rmse, mae, mape, processed_products)

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
    """Lightweight endpoint: just read cached forecasts from DB."""
    try:
        df = pd.read_sql(f"SELECT date, forecast_amount FROM {TABLE_SALES_FORECAST} ORDER BY date ASC;", engine)
        return jsonify({"status": "success", "forecast": df.to_dict(orient="records")}), 200
    except Exception as e:
        return jsonify({"status": "error", "message": str(e)}), 500

@app.route("/run-forecast", methods=["POST"])
def run_forecast_endpoint():
    """Heavy endpoint: manually trigger training and update forecasts (batched)."""
    try:
        # Optional batching via query param: /run-forecast?batch=1
        batch = int(request.args.get("batch", "1"))
        start = max(0, (batch - 1) * MAX_PRODUCTS_PER_RUN)
        end = start + MAX_PRODUCTS_PER_RUN
        inv = fetch_inventory().iloc[start:end]

        result = run_pipeline(inv)
        return jsonify({"status": "success", "batch": batch, **result}), 200
    except Exception as e:
        import traceback
        print("Run forecast error:", str(e))
        print(traceback.format_exc())
        return jsonify({"status": "error", "message": str(e)}), 500

if __name__ == "__main__":
    # Do not auto-run training; only serve endpoints.
    app.run(host="0.0.0.0", port=int(os.getenv("PORT", 5000)))
