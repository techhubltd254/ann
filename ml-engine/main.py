"""
KICC ML Engine — FastAPI microservice
Runs: recommender, Trust Score computation, predictive analytics, anomaly detection.
Communicates with Laravel via Redis queue + REST API.
"""
from fastapi import FastAPI, HTTPException
from contextlib import asynccontextmanager
import os, redis, json
from dotenv import load_dotenv

load_dotenv()

REDIS_URL = os.getenv("REDIS_URL", "redis://localhost:6379/0")

@asynccontextmanager
async def lifespan(app: FastAPI):
    app.state.redis = redis.from_url(REDIS_URL, decode_responses=True)
    print(f"[ml-engine] connected to redis at {REDIS_URL}")
    yield
    app.state.redis.close()

app = FastAPI(
    title="KICC ML Engine",
    version="1.0.0",
    lifespan=lifespan,
    docs_url="/docs",
)

@app.get("/health")
def health():
    return {"status": "ok", "service": "ml-engine"}

@app.post("/recommend")
def recommend(user_id: int, limit: int = 10):
    """Get hybrid recommendations for a user."""
    from services.recommender import HybridRecommender
    recs = HybridRecommender().get_recommendations(user_id, limit)
    return {"user_id": user_id, "recommendations": recs}

@app.post("/trust-score")
def compute_trust(vendor_id: int):
    """Compute Trust Score for a vendor."""
    from services.trust_score import TrustScoreEngine
    score = TrustScoreEngine().compute(vendor_id)
    return {"vendor_id": vendor_id, "trust_score": score, "grade": TrustScoreEngine.to_grade(score)}

@app.post("/predict/footfall")
def predict_footfall(county_id: int, days: int = 30):
    """Predict tourism footfall for a county."""
    from services.predictive import PredictiveAnalytics
    forecast = PredictiveAnalytics().forecast_footfall(county_id, days)
    return {"county_id": county_id, "forecast": forecast}

@app.post("/detect/anomalies")
def detect_anomalies(entity_type: str, entity_id: int):
    """Detect anomalies in payment/review/transaction patterns."""
    from services.anomaly import AnomalyDetector
    result = AnomalyDetector().detect(entity_type, entity_id)
    return {"entity_type": entity_type, "entity_id": entity_id, "anomalies": result}
