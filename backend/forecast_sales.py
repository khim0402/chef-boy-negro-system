# backend/forecast_sales.py
import os
import pandas as pd
import numpy as np
import lightgbm as lgb
from flask import Flask, jsonify
from sklearn.model_selection import train_test_split
from sklearn.metrics import mean_squared_error, mean_absolute_error
from datetime import datetime, timedelta
from sqlalchemy import create_engine, text

# --- Database credentials from environment ---
DB_HOST = os.getenv("DB_HOST", "dpg-d4i43m75r7bs73c7gvl0-a")
DB_USER = os.getenv("DB_USER", "chefboyuser")
DB_PASS = os.getenv("DB_PASS", "jA9GdmJDas6lbzWYzIybF50GqoHvOqwF")
DB_NAME = os.getenv("DB_NAME", "chefboynegro")

DB_URL = f"postgresql+psycopg2://{DB_USER}:{DB_PASS}@{DB_HOST}/{DB_NAME}"
engine = create_engine(DB_URL, pool_pre_ping=True)

# --- Flask app setup ---
app = Flask(__name__)

@app.route("/health")
def health():
    try:
        with engine.connect() as conn:
            conn.execute(text("SELECT 1"))
        return jsonify({"status": "ok", "db": "connected"})
    except Exception as e:
        return jsonify({"status": "error", "detail": str(e)}), 500

# --- Entry point for local dev (Windows) ---
if __name__ == "__main__":
    app.run(host="0.0.0.0", port=10000, debug=True)

FORECAST_HORIZON_DAYS = 7
MIN_HISTORY_POINTS = 7
MODEL_NAME = "LightGBM"

TABLE_INVENTORY = "inventory"
TABLE_SALES_ITEMS = "sales_items"
TABLE_SALES = "sales"
TABLE_PRODUCT_FORECAST = "product_forecast"
TABLE_SALES_FORECAST = "sales_forecast"
TABLE_FORECAST_METRICS = "forecast_metrics"


def fetch_inventory():
    df = pd.read_sql(
        f"SELECT product_id, name, qty, threshold FROM {TABLE_INVENTORY} ORDER BY product_id ASC;",
        engine
    )
    # Guard against header-row artifacts
    df = df[df['product_id'] != 'product_id']
    return df


def fetch_product_sales_series(product_id: int) -> pd.DataFrame:
    sql = f"""
        SELECT DATE(created_at) AS date, SUM(qty) AS qty_sold
        FROM {TABLE_SALES_ITEMS}
        WHERE product_id = {product_id}
        GROUP BY DATE(created_at)
        ORDER BY DATE(created_at) ASC;
    """
    df = pd.read_sql(sql, engine)
    if df.empty:
        return df
    df['date'] = pd.to_datetime(df['date'])
    # Fill missing dates with 0 qty
    df = df.set_index('date').asfreq('D', fill_value=0).reset_index()
    return df


def fetch_actual_sales_series() -> pd.DataFrame:
    sql = f"""
        SELECT DATE(created_at) AS date, SUM(total_amount) AS actual_sales
        FROM {TABLE_SALES}
        GROUP BY DATE(created_at)
        ORDER BY DATE(created_at) ASC;
    """
    df = pd.read_sql(sql, engine)
    df['date'] = pd.to_datetime(df['date'])
    return df


def fetch_avg_prices() -> dict:
    sql = f"""
        SELECT product_id, AVG(price) AS avg_price
        FROM {TABLE_SALES_ITEMS}
        WHERE price IS NOT NULL AND price > 0
        GROUP BY product_id;
    """
    df = pd.read_sql(sql, engine)
    return {int(row['product_id']): float(row['avg_price']) for _, row in df.iterrows()}


def build_features(df: pd.DataFrame) -> pd.DataFrame:
    df = df.copy()
    df['day'] = df['date'].dt.day
    df['month'] = df['date'].dt.month
    df['weekday'] = df['date'].dt.weekday
    df['week'] = df['date'].dt.isocalendar().week.astype(int)
    df['lag1'] = df['qty_sold'].shift(1)
    df['lag7'] = df['qty_sold'].shift(7)
    df['roll3'] = df['qty_sold'].rolling(3).mean()
    df['roll7'] = df['qty_sold'].rolling(7).mean()
    return df.dropna().reset_index(drop=True)


def train_lightgbm(X_train, y_train, X_test, y_test):
    model = lgb.LGBMRegressor(
        objective='regression',
        n_estimators=300,
        learning_rate=0.05,
        random_state=42,
        verbose=-1
    )
    model.fit(X_train, y_train, eval_set=[(X_test, y_test)], eval_metric='rmse')
    y_pred = model.predict(X_test)
    rmse = float(np.sqrt(mean_squared_error(y_test, y_pred)))
    return model, rmse


def forecast_next_days(df_series: pd.DataFrame, model, horizon=FORECAST_HORIZON_DAYS):
    history = df_series.copy().sort_values('date').reset_index(drop=True)
    outputs = []
    last_date = history['date'].iloc[-1]
    last_values = history['qty_sold'].tolist()

    for i in range(1, horizon + 1):
        next_date = last_date + timedelta(days=i)
        lag1 = last_values[-1]
        lag7 = last_values[-7] if len(last_values) >= 7 else lag1
        roll3 = np.mean(last_values[-3:]) if len(last_values) >= 3 else np.mean(last_values)
        roll7 = np.mean(last_values[-7:]) if len(last_values) >= 7 else np.mean(last_values)

        feat = pd.DataFrame([{
            'day': next_date.day,
            'month': next_date.month,
            'weekday': next_date.weekday(),
            'week': next_date.isocalendar().week,
            'lag1': lag1,
            'lag7': lag7,
            'roll3': roll3,
            'roll7': roll7
        }])

        yhat = float(model.predict(feat)[0])
        yhat = max(0.0, yhat)
        outputs.append((next_date.date(), yhat))
        last_values.append(yhat)

    return outputs


def upsert_product_forecast(product_id: int, forecasts: list):
    # forecasts: list of (date, qty)
    with engine.begin() as conn:
        for fdate, qty in forecasts:
            sql = text(f"""
                INSERT INTO {TABLE_PRODUCT_FORECAST} (product_id, forecast_date, forecast_qty, model_used, created_at)
                VALUES (:pid, :fdate, :qty, :model, NOW())
                ON DUPLICATE KEY UPDATE forecast_qty = :qty, model_used = :model;
            """)
            conn.execute(sql, {"pid": product_id, "fdate": str(fdate), "qty": float(qty), "model": MODEL_NAME})


def upsert_sales_forecast(daily_pesos: dict):
    # daily_pesos: dict of date -> amount in pesos
    with engine.begin() as conn:
        for fdate, amount in sorted(daily_pesos.items()):
            sql = text(f"""
                INSERT INTO {TABLE_SALES_FORECAST} (date, forecast_amount, model_used)
                VALUES (:fdate, :amount, :model)
                ON DUPLICATE KEY UPDATE forecast_amount = :amount, model_used = :model;
            """)
            conn.execute(sql, {"fdate": str(fdate), "amount": float(round(amount, 2)), "model": MODEL_NAME})


def insert_forecast_metrics(model_used: str, mape: float, rmse: float, mae: float, horizon: int):
    with engine.begin() as conn:
        sql = text(f"""
            INSERT INTO {TABLE_FORECAST_METRICS} (model_used, mape, rmse, mae, trained_on, forecast_horizon, created_at)
            VALUES (:model, :mape, :rmse, :mae, NOW(), :horizon, NOW());
        """)
        conn.execute(sql, {
            "model": model_used,
            "mape": float(round(mape, 4)),
            "rmse": float(round(rmse, 4)),
            "mae": float(round(mae, 4)),
            "horizon": int(horizon)
        })


def main():
    inventory_df = fetch_inventory()
    avg_prices = fetch_avg_prices()
    daily_totals_pesos = {}  # date -> pesos
    print(f"Loaded products: {len(inventory_df)}")

    # Per-product training and forecasting
    for _, row in inventory_df.iterrows():
        try:
            pid = int(row['product_id'])
        except (ValueError, TypeError):
            continue

        name = str(row.get('name', ''))
        stock = int(row.get('qty', 0))
        thresh = int(row.get('threshold', 0))

        series = fetch_product_sales_series(pid)
        if series.empty or len(series) < MIN_HISTORY_POINTS:
            print(f"SKIP pid={pid} ({name}): insufficient history ({len(series)} days)")
            continue

        feat_df = build_features(series)
        X = feat_df[['day', 'month', 'weekday', 'week', 'lag1', 'lag7', 'roll3', 'roll7']]
        y = feat_df['qty_sold']

        X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, shuffle=False)
        model, rmse = train_lightgbm(X_train, y_train, X_test, y_test)

        forecasts = forecast_next_days(series, model, horizon=FORECAST_HORIZON_DAYS)
        upsert_product_forecast(pid, forecasts)

        price = avg_prices.get(pid, 0.0)
        for fdate, qty in forecasts:
            daily_totals_pesos[fdate] = daily_totals_pesos.get(fdate, 0.0) + (qty * price)

    # Save aggregated daily pesos forecast for chart and metrics
    upsert_sales_forecast(daily_totals_pesos)

    # Compute metrics on overlapping dates between actuals and forecasts
    actual_df = fetch_actual_sales_series()
    forecast_df = pd.read_sql(f"SELECT date, forecast_amount FROM {TABLE_SALES_FORECAST} ORDER BY date ASC;", engine)

    actual_df['date'] = pd.to_datetime(actual_df['date'])
    forecast_df['date'] = pd.to_datetime(forecast_df['date'])

    merged = pd.merge(actual_df, forecast_df, on="date", how="inner")
    if merged.empty:
        rmse = mae = mape = 0.0
    else:
        rmse = float(np.sqrt(mean_squared_error(merged['actual_sales'], merged['forecast_amount'])))
        mae = float(mean_absolute_error(merged['actual_sales'], merged['forecast_amount']))
        safe_actual = merged['actual_sales'].replace(0, np.nan)
        mape = float(np.nanmean(np.abs((safe_actual - merged['forecast_amount']) / safe_actual)) * 100)

    insert_forecast_metrics(MODEL_NAME, mape, rmse, mae, FORECAST_HORIZON_DAYS)
    print(f"Metrics saved: RMSE={rmse:.2f}, MAE={mae:.2f}, MAPE={mape:.2f}%")


# === Flask app for Render ===
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
def run_forecast():
    try:
        main()
        return jsonify({"status": "success", "message": "Forecast updated"}), 200
    except Exception as e:
        return jsonify({"status": "error", "message": str(e)}), 500


if __name__ == "__main__":
    # Render will expose this service; keep port consistent or use PORT env var
    port = int(os.getenv("PORT", "10000"))
    app.run(host="0.0.0.0", port=port)
