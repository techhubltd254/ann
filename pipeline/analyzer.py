"""Scene analyzer using OpenRouter (Kimi K3).

Analyzes video content to determine:
- Scene type: exhibition_hall, booth_closeup, walkthrough, outdoor, crowd
- Optimal depth strength (0.5-3.0)
- Recommended pipeline: sbs, splat, hybrid
- Keyframe indices for refinement
"""

import json
import logging
import os
from pathlib import Path
from typing import Optional

import requests
from dotenv import load_dotenv

load_dotenv()

logger = logging.getLogger(__name__)


class SceneAnalysis:
    def __init__(self, data: dict):
        self.scene_type: str = data.get("scene_type", "walkthrough")
        self.depth_strength: float = float(data.get("depth_strength", 1.5))
        self.convergence: float = float(data.get("convergence", 0.3))
        self.pipeline: str = data.get("pipeline", "sbs")
        self.keyframe_indices: list[int] = data.get("keyframe_indices", [0])
        self.confidence: float = float(data.get("confidence", 0.0))
        self.description: str = data.get("description", "")
        self.error: Optional[str] = data.get("error")

    @classmethod
    def default(cls, reason: str = "Analysis unavailable") -> "SceneAnalysis":
        return cls({
            "scene_type": "walkthrough",
            "depth_strength": 1.5,
            "convergence": 0.3,
            "pipeline": "sbs",
            "keyframe_indices": [0],
            "confidence": 0.0,
            "description": reason,
            "error": reason,
        })

    def to_dict(self) -> dict:
        return {
            "scene_type": self.scene_type,
            "depth_strength": self.depth_strength,
            "convergence": self.convergence,
            "pipeline": self.pipeline,
            "keyframe_indices": self.keyframe_indices,
            "confidence": self.confidence,
            "description": self.description,
        }


class Analyzer:
    """Scene intelligence via OpenRouter (Kimi K3)."""

    MODEL = "moonshotai/kimi-k3-mini"
    API_URL = "https://openrouter.ai/api/v1/chat/completions"

    def __init__(self, api_key: Optional[str] = None):
        self.api_key = api_key or os.getenv("OPENROUTER_API_KEY")
        if not self.api_key:
            logger.warning("No OpenRouter API key set — using default analysis")

    def analyze(self, video_path: str, sample_frames: list[str] | None = None) -> SceneAnalysis:
        if not self.api_key:
            return SceneAnalysis.default("No API key configured; deep analysis skipped")

        prompt = self._build_prompt(video_path, sample_frames)
        try:
            resp = requests.post(
                self.API_URL,
                headers={
                    "Authorization": f"Bearer {self.api_key}",
                    "Content-Type": "application/json",
                },
                json={
                    "model": self.MODEL,
                    "messages": [
                        {"role": "system", "content": "You are a 3D scene analysis expert. Return ONLY valid JSON."},
                        {"role": "user", "content": prompt},
                    ],
                    "temperature": 0.1,
                    "max_tokens": 500,
                },
                timeout=30,
            )
            resp.raise_for_status()
            content = resp.json()["choices"][0]["message"]["content"]
            data = json.loads(self._extract_json(content))
            return SceneAnalysis(data)
        except Exception as e:
            logger.error(f"Analysis failed: {e}")
            return SceneAnalysis.default(f"Analysis error: {e}")

    def _build_prompt(self, video_path: str, sample_frames: list[str] | None) -> str:
        base = f"""Analyze this video for 3D conversion: {Path(video_path).name}

Return JSON with:
- scene_type: one of "exhibition_hall", "booth_closeup", "walkthrough", "outdoor", "crowd"
- depth_strength: float 0.5-3.0 (higher = more pop-out)
- convergence: float 0.0-1.0 (how much depth goes behind screen)
- pipeline: "sbs" for static scenes, "splat" for walkable scenes, "hybrid" for both
- keyframe_indices: list of integers (frame numbers best for refinement, max 5)
- confidence: float 0.0-1.0
- description: brief scene description (1 sentence)
"""
        if sample_frames:
            base += f"\nFrames provided: {len(sample_frames)} samples from start/mid/end."
        return base

    @staticmethod
    def _extract_json(text: str) -> str:
        start = text.find("{")
        end = text.rfind("}")
        if start != -1 and end != -1:
            return text[start : end + 1]
        return text
