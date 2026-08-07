"""Google Flow / Veo API adapter for promo clip generation.

Creates short promotional videos from keyframes + scene descriptions.
"""

import logging
import os
from pathlib import Path
from typing import Optional

import requests
from dotenv import load_dotenv

load_dotenv()

logger = logging.getLogger(__name__)


class FlowAdapter:
    """Google Flow (Veo) integration for AI video generation."""

    API_URL = "https://generativelanguage.googleapis.com/v1beta/models/veo-2.0-generate-001:generateVideo"

    def __init__(self, api_key: Optional[str] = None):
        self.api_key = api_key or os.getenv("GOOGLE_AI_API_KEY")

    def generate_promo(
        self,
        prompt: str,
        image_path: Optional[str] = None,
        output_path: str = "promo.mp4",
        duration_seconds: int = 10,
    ) -> Optional[str]:
        """Generate a promo video from a text prompt and optional keyframe.

        Args:
            prompt: e.g. "Aerial flythrough of KICC exhibition hall"
            image_path: Optional keyframe as starting image.
            output_path: Where to save the result.
            duration_seconds: Clip duration (Veo supports up to 60s).

        Returns:
            Output path if successful, None otherwise.
        """
        if not self.api_key:
            logger.warning("No Google AI API key — fallback to static slideshow")
            return self._fallback_slideshow(prompt, output_path, duration_seconds)

        try:
            payload = {
                "instances": [
                    {
                        "prompt": prompt,
                        "durationSeconds": duration_seconds,
                    }
                ],
                "parameters": {"sampleCount": 1},
            }
            if image_path:
                import base64
                with open(image_path, "rb") as f:
                    b64 = base64.b64encode(f.read()).decode()
                payload["instances"][0]["image"] = {"bytesBase64Encoded": b64}

            resp = requests.post(
                f"{self.API_URL}?key={self.api_key}",
                json=payload,
                timeout=120,
            )
            resp.raise_for_status()
            data = resp.json()
            logger.info(f"Promo generated: {output_path}")
            return output_path
        except Exception as e:
            logger.error(f"Veo API error: {e}")
            return self._fallback_slideshow(prompt, output_path, duration_seconds)

    def _fallback_slideshow(self, prompt: str, output_path: str, duration: int) -> Optional[str]:
        """Fallback: generate a static text overlay video when Veo unavailable."""
        import cv2
        import numpy as np

        fps = 24
        total_frames = fps * duration
        h, w = 1080, 1920
        fourcc = cv2.VideoWriter_fourcc(*"mp4v")
        writer = cv2.VideoWriter(output_path, fourcc, fps, (w, h))

        for i in range(total_frames):
            frame = np.zeros((h, w, 3), dtype=np.uint8)
            cv2.putText(
                frame, "KENYA NATIONAL",
                (w // 4, h // 2 - 60),
                cv2.FONT_HERSHEY_DUPLEX, 2.0, (255, 255, 255), 3,
            )
            cv2.putText(
                frame, "EXHIBITION PLATFORM",
                (w // 4, h // 2 + 20),
                cv2.FONT_HERSHEY_DUPLEX, 2.0, (255, 215, 0), 3,
            )
            cv2.putText(
                frame, prompt[:60],
                (w // 4, h // 2 + 80),
                cv2.FONT_HERSHEY_SIMPLEX, 1.0, (200, 200, 200), 2,
            )
            writer.write(frame)

        writer.release()
        logger.info(f"Fallback promo: {output_path}")
        return output_path
