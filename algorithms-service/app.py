"""KICC Algorithms API — FastAPI production server."""
import json, os, sys
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from fastapi import FastAPI
from pydantic import BaseModel
import uvicorn
from kicc_algorithms import KiccPlatform

_kicc = None
def get():
    global _kicc
    if _kicc is None:
        _kicc = KiccPlatform(json.loads(os.environ.get("KICC_CONFIG_OVERRIDES", "{}")))
    return _kicc

app = FastAPI(title="KICC Algorithms", version="2.0.0")

class QReq(BaseModel):
    delivery_rate: float; adverse_rate: float; trust_grade: str
    completeness: float; avg_review: float; media_tier: int

@app.post("/quality")
def quality(req: QReq):
    return get().quality_of(**req.model_dump())

class KybReq(BaseModel):
    phone_verified: bool; kra_pin: str|None=None; pin_validated: bool=False; asset_verified: bool=False

@app.post("/kyb")
def kyb(req: KybReq):
    return get().onboard(**req.model_dump())

class ScreenReq(BaseModel): name: str

@app.post("/screen")
def screen(req: ScreenReq):
    from kicc_algorithms.algorithms.screening import Screener
    r = Screener(get().config).screen(req.name)
    return r

class PoolReq(BaseModel):
    contributions: list[dict] = []
    qualities: dict[int, float] = {}

@app.post("/pool/distribute")
def pool_dist(req: PoolReq):
    return get().run_pool_distribution(req.contributions, req.qualities)

class AnonReq(BaseModel):
    rows: list[dict]; group_by_key: str = "county_id"; k: int = 5

@app.post("/anonymize")
def anonymize(req: AnonReq):
    a = get().anonymizer; a.k = req.k
    return a.anonymize(req.rows, req.group_by_key)

class ClassifyReq(BaseModel):
    name: str = ""; gmv: float = 0; water: float = 0; roads: float = 0

@app.post("/classify")
def classify(req: ClassifyReq):
    return get().classify_counties([req.model_dump()])[0]

@app.get("/health")
def health():
    return {"status": "ok", "algorithms": 20}

if __name__ == "__main__":
    uvicorn.run("app:app", host="0.0.0.0", port=int(os.environ.get("KICC_API_PORT","8400")), workers=os.cpu_count() or 2)
