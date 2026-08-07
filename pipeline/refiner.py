"""Keyframe refiner using Google AI (Nano Banana 2).

Enhances selected keyframes from the video for:
- Better texture quality in SBS output
- Booth branding overlay assets
- Promotional material thumbnails
"""

import base64
import logging
import os
from pathlib import Path
from typing import Optional

import cv2
import numpy as np
import requests
from dotenv import load_dotenv

load_dotenv()

logger = logging.getLogger(__name__)


class Refiner:
    """Keyframe enhancement via Google AI API (Nano Banana 2 / Imagen)."""

    API_URL = "https://generativelanguage.googleapis.com/v1beta/models/imagen-3.0-generate-001:predict"

    def __init__(self, api_key: Optional[str] = None):
        self.api_key = api_key or os.getenv("GOOGLE_AI_API_KEY")

    def enhance_frame(self, frame: np.ndarray, prompt: str = "enhance detail, sharp texture") -> Optional[np.ndarray]:
        """Upscale and enhance a single keyframe.

        Falls back to OpenCV super-resolution if API unavailable.
        """
        if self.api_key:
            result = self._api_enhance(frame, prompt)
            if result is not None:
                return result

        return self._cv_upscale(frame)

    def enhance_frames(
        self, frames: list[np.ndarray], output_dir: str
    ) -> list[str]:
        """Enhance multiple keyframes and save to output_dir."""
        Path(output_dir).mkdir(parents=True, exist_ok=True)
        paths = []
        for i, frame in enumerate(frames):
            enhanced = self.enhance_frame(frame)
            path = str(Path(output_dir) / f"keyframe_enhanced_{i:04d}.png")
            cv2.imwrite(path, enhanced)
            paths.append(path)
        return paths

    def _api_enhance(self, frame: np.ndarray, prompt: str) -> Optional[np.ndarray]:
        try:
            _, buf = cv2.imencode(".png", frame)
            b64 = base64.b64encode(buf).decode()

            resp = requests.post(
                f"{self.API_URL}?key={self.api_key}",
                json={
                    "instances": [{"prompt": prompt, "image": {"bytesBase64Encoded": b64}}],
                    "parameters": {"sampleCount": 1},
                },
                timeout=30,
            )
            resp.raise_for_status()
            data = resp.json()
            b64_out = data["predictions"][0]["bytesBase64Encoded"]
            buf = np.frombuffer(base64.b64decode(b64_out), dtype=np.uint8)
            return cv2.imdecode(buf, cv2.IMREAD_COLOR)
        except Exception as e:
            logger.warning(f"API enhance failed: {e} — falling back to OpenCV")
            return None

    @staticmethod
    def _cv_upscale(frame: np.ndarray, scale: float = 2.0) -> np.ndarray:
        """Upscale using Lanczos interpolation."""
        h, w = frame.shape[:2]
        return cv2.resize(frame, (int(w * scale), int(h * scale)), interpolation=cv2.INTER_LANCZOS4)
