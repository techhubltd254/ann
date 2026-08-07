"""Depth estimation worker using Depth Anything V2.

Processes video frames to produce depth maps.
Supports CPU (DAv2 Small) and GPU (DAv2 Large) modes.
"""

import logging
import os
import tempfile
from pathlib import Path
from typing import Optional

import cv2
import numpy as np
import torch
from PIL import Image

logger = logging.getLogger(__name__)


class DepthWorker:
    """Depth Anything V2 wrapper for frame-by-frame depth estimation."""

    MODEL_SIZES = {
        "small": "depth-anything/Depth-Anything-V2-Small-hf",
        "base": "depth-anything/Depth-Anything-V2-Base-hf",
        "large": "depth-anything/Depth-Anything-V2-Large-hf",
    }

    def __init__(self, model_size: str = "small", device: Optional[str] = None):
        if device is None:
            device = "cuda" if torch.cuda.is_available() else "cpu"
        self.device = torch.device(device)
        self.model_size = model_size
        self.model = None
        self.processor = None

        logger.info(f"DepthWorker initialised: model={model_size}, device={device}")

    def load_model(self):
        if self.model is not None:
            return
        from transformers import AutoImageProcessor, AutoModelForDepthEstimation

        model_name = self.MODEL_SIZES.get(self.model_size, self.MODEL_SIZES["small"])
        logger.info(f"Loading depth model: {model_name}")
        self.processor = AutoImageProcessor.from_pretrained(model_name)
        self.model = AutoModelForDepthEstimation.from_pretrained(model_name).to(self.device)
        self.model.eval()

    def estimate(self, frame: np.ndarray) -> np.ndarray:
        """Estimate depth map for a single BGR frame.

        Returns:
            Depth map as float32 numpy array (same size as input), range ~0-1.
        """
        self.load_model()
        rgb = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)
        pil = Image.fromarray(rgb)

        inputs = self.processor(images=pil, return_tensors="pt").to(self.device)

        with torch.no_grad():
            outputs = self.model(**inputs)

        depth = outputs.predicted_depth.squeeze().cpu().numpy()

        depth_resized = cv2.resize(depth, (frame.shape[1], frame.shape[0]), interpolation=cv2.INTER_CUBIC)
        depth_norm = (depth_resized - depth_resized.min()) / (
            depth_resized.max() - depth_resized.min() + 1e-8
        )
        return depth_norm.astype(np.float32)

    def process_video(
        self,
        video_path: str,
        output_dir: str,
        max_frames: Optional[int] = None,
        skip_frames: int = 0,
    ) -> list[str]:
        """Extract depth maps for every frame of a video.

        Args:
            video_path: Path to input video.
            output_dir: Where to save depth .npy files.
            max_frames: Limit total frames processed.
            skip_frames: Process every Nth frame (1 = all).

        Returns:
            List of paths to saved depth maps.
        """
        Path(output_dir).mkdir(parents=True, exist_ok=True)
        cap = cv2.VideoCapture(video_path)
        total = int(cap.get(cv2.CAP_PROP_FRAME_COUNT))
        depth_paths = []
        frame_idx = 0
        saved_idx = 0

        logger.info(f"Processing {video_path}: {total} frames, skip={skip_frames}, max={max_frames}")

        while True:
            ret, frame = cap.read()
            if not ret:
                break
            if frame_idx % (skip_frames + 1) != 0:
                frame_idx += 1
                continue

            depth_map = self.estimate(frame)
            out_path = Path(output_dir) / f"depth_{saved_idx:06d}.npy"
            np.save(out_path, depth_map)
            depth_paths.append(str(out_path))

            saved_idx += 1
            frame_idx += 1
            if saved_idx % 50 == 0:
                logger.info(f"  Depth progress: {saved_idx}/{total} frames")

            if max_frames and saved_idx >= max_frames:
                break

        cap.release()
        logger.info(f"Depth complete: {len(depth_paths)} maps -> {output_dir}")
        return depth_paths

    def estimate_from_path(self, image_path: str) -> np.ndarray:
        """Depth estimate from an image file path."""
        frame = cv2.imread(image_path)
        if frame is None:
            raise FileNotFoundError(f"Cannot read image: {image_path}")
        return self.estimate(frame)
