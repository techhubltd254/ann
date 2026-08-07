"""Gaussian Splatting worker for interactive 3D reconstruction.

Produces 3D Gaussian Splatting point clouds from video walkthroughs.
Output .ply/.splat files for WebGL viewer consumption.
"""

import json
import logging
import subprocess
from pathlib import Path
from typing import Optional

import numpy as np

logger = logging.getLogger(__name__)


class SplatWorker:
    """Orchestrates 3D Gaussian Splatting reconstruction.

    Currently provides scaffolding:
    - Frame extraction for COLMAP compatibility
    - SfM sparse reconstruction via COLMAP
    - Gaussian Splatting training (3DGS)
    - Output compression for web delivery

    NOTE: Full 3DGS training requires a GPU. When no GPU is available,
    this worker generates a placeholder that the interactive viewer
    can fall back to a flat 360-panorama mode.
    """

    def __init__(self, output_dir: str = "splat_output"):
        self.output_dir = Path(output_dir)
        self.output_dir.mkdir(parents=True, exist_ok=True)

    def extract_frames(self, video_path: str, frame_interval: int = 30) -> list[str]:
        """Extract frames from video for SfM processing.

        Args:
            video_path: Input video path.
            frame_interval: Extract every Nth frame.

        Returns:
            List of extracted frame paths.
        """
        import cv2

        cap = cv2.VideoCapture(video_path)
        frames_dir = self.output_dir / "input_frames"
        frames_dir.mkdir(parents=True, exist_ok=True)

        frame_paths = []
        idx = 0
        saved = 0

        while True:
            ret, frame = cap.read()
            if not ret:
                break
            if idx % frame_interval == 0:
                path = str(frames_dir / f"frame_{saved:06d}.jpg")
                cv2.imwrite(path, frame)
                frame_paths.append(path)
                saved += 1
            idx += 1

        cap.release()
        logger.info(f"Extracted {saved} frames for splatting from {video_path}")
        return frame_paths

    def run_colmap(self, frames_dir: str) -> bool:
        """Run COLMAP sparse reconstruction.

        Returns True if COLMAP succeeds, False otherwise.
        """
        colmap_bin = "colmap"
        db_path = str(self.output_dir / "database.db")
        sparse_dir = str(self.output_dir / "sparse")

        try:
            subprocess.run(
                [colmap_bin, "feature_extractor",
                 "--database_path", db_path,
                 "--image_path", frames_dir],
                check=True, capture_output=True, timeout=600,
            )
            subprocess.run(
                [colmap_bin, "exhaustive_matcher",
                 "--database_path", db_path],
                check=True, capture_output=True, timeout=600,
            )
            Path(sparse_dir).mkdir(parents=True, exist_ok=True)
            subprocess.run(
                [colmap_bin, "mapper",
                 "--database_path", db_path,
                 "--image_path", frames_dir,
                 "--output_path", sparse_dir],
                check=True, capture_output=True, timeout=3600,
            )
            logger.info("COLMAP sparse reconstruction complete")
            return True
        except FileNotFoundError:
            logger.warning("COLMAP not installed — skipping SfM")
            return False
        except (subprocess.CalledProcessError, subprocess.TimeoutExpired) as e:
            logger.warning(f"COLMAP failed: {e}")
            return False

    def train_3dgs(self, sparse_dir: str) -> Optional[str]:
        """Train 3D Gaussian Splatting model.

        Placeholder: actual 3DGS training requires:
          git clone https://github.com/graphdeco-inria/gaussian-splatting
          and a CUDA-capable GPU.

        Returns path to .ply file or None.
        """
        logger.warning("3DGS training not yet implemented — requires GPU and gaussian-splatting repo")
        return None

    def generate_placeholder(self, video_path: str) -> str:
        """Generate a placeholder splat JSON for WebGL viewer fallback.

        Creates spherical metadata so the viewer can show a 360° mode.
        """
        import cv2

        cap = cv2.VideoCapture(video_path)
        w = int(cap.get(cv2.CAP_PROP_FRAME_WIDTH))
        h = int(cap.get(cv2.CAP_PROP_FRAME_HEIGHT))
        cap.release()

        meta = {
            "type": "360_fallback",
            "source": Path(video_path).name,
            "width": w,
            "height": h,
            "version": "1.0",
            "message": "Full 3DGS reconstruction requires GPU. Using 360-panorama fallback.",
        }
        meta_path = str(self.output_dir / "splat_placeholder.json")
        with open(meta_path, "w") as f:
            json.dump(meta, f, indent=2)

        logger.info(f"Placeholder splat metadata: {meta_path}")
        return meta_path
