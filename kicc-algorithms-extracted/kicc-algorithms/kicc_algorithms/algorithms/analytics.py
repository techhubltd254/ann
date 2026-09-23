"""Algorithm 7: AnalyticsService - forecast + revenue ratio.

Forecast: weighted moving average over monthly revenue (recency-weighted),
with a trend term. Regression guard: the production 144x bug came from
double unit scaling; here forecast and actuals share one base currency unit
and the ratio is actual/forecast, never forecast/actual.
"""
from __future__ import annotations


class Analytics:
    def forecast_next_month(self, monthly_revenue: list[float]) -> dict:
        if not monthly_revenue:
            return {"forecast": 0.0, "trend": 0.0, "n": 0}
        n = len(monthly_revenue)
        weights = list(range(1, n + 1))          # recent months weigh more
        wsum = sum(weights)
        wma = sum(w * r for w, r in zip(weights, monthly_revenue)) / wsum
        trend = 0.0
        if n >= 2:
            trend = (monthly_revenue[-1] - monthly_revenue[-2]) / max(1e-9, abs(monthly_revenue[-2]))
        forecast = max(0.0, wma * (1 + 0.25 * max(-1.0, min(1.0, trend))))
        return {"forecast": round(forecast, 2), "trend": round(trend, 4), "n": n}

    def revenue_ratio(self, actual: float, forecast: float) -> float:
        if forecast <= 0:
            return 0.0
        return round(actual / forecast, 4)
