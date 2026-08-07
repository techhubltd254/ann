"""Stereo SBS (Side-by-Side) generation worker.

Converts 2D frames + depth maps into SBS 3D video.
Supports configurable disparity, inpainting, and 4K upscale.
"""

import logging
import subprocess
from pathlib import Path
from typing import Optional

import cv2
import numpy as np
from tqdm import tqdm

logger = logging.getLogger(__name__)


class StereoWorker:
    """Generates SBS 3D video from 2D frames + depth maps."""

    def __init__(
        self,
        depth_strength: float = 1.5,
        convergence: float = 0.3,
        upscale_factor: int = 2,
    ):
        self.depth_strength = depth_strength
        self.convergence = convergence
        self.upscale_factor = upscale_factor

    def generate_sbs_frame(
        self, frame: np.ndarray, depth_map: np.ndarray
    ) -> np.ndarray:
        """Generate SBS (side-by-side) frame from 2D frame + depth.

        Args:
            frame: BGR frame (H, W, 3).
            depth_map: Float32 depth (H, W), range 0-1.

        Returns:
            SBS frame (H, W*2, 3) — left eye original, right eye shifted.
        """
        h, w = frame.shape[:2]
        depth_strength_px = int(w * 0.04 * self.depth_strength)
        convergence_offset = int(w * 0.02 * self.convergence)

        shift_map = (depth_map - 0.5) * 2.0
        shift_map = np.clip(shift_map, -1.0, 1.0)

        right_eye = np.zeros_like(frame)
        for y in range(h):
            for ch in range(3):
                row = frame[y, :, ch].astype(np.float32)
                shift_row = shift_map[y, :]
                shift_pixels = (shift_row * depth_strength_px + convergence_offset).astype(np.int32)

                src_indices = np.arange(w)
                dst_indices = np.clip(src_indices + shift_pixels, 0, w - 1)
                right_eye[y, :, ch] = row[dst_indices]

        right_eye = self._inpaint_holes(right_eye, frame)

        sbs = np.hstack([frame, right_eye])
        if self.upscale_factor > 1:
            sbs = cv2.resize(
                sbs,
                (w * 2 * self.upscale_factor, h * self.upscale_factor),
                interpolation=cv2.INTER_CUBIC,
            )
        return sbs

    def _inpaint_holes(self, right_eye: np.ndarray, original: np.ndarray) -> np.ndarray:
        """Inpaint missing pixels in right eye using original frame."""
        mask = np.all(right_eye == 0, axis=2).astype(np.uint8) * 255
        kernel = np.ones((3, 3), np.uint8)
        mask = cv2.dilate(mask, kernel, iterations=1)

        if cv2.countNonZero(mask) > 0:
            right_eye = cv2.inpaint(right_eye, mask, inpaintRadius=3, flags=cv2.INPAINT_TELEA)
        return right_eye

    def process_video(
        self,
        video_path: str,
        depth_dir: str,
        output_path: str,
        fps: Optional[float] = None,
    ) -> str:
        """Generate SBS video from original video + depth maps directory.

        Args:
            video_path: Original 2D video.
            depth_dir: Directory with depth_*.npy files (one per frame).
            output_path: Output MP4 path.
            fps: Output fps (default: auto from input).

        Returns:
            Path to generated SBS video.
        """
        import glob

        cap = cv2.VideoCapture(video_path)
        if fps is None:
            fps = cap.get(cv2.CAP_PROP_FPS)
        orig_w = int(cap.get(cv2.CAP_PROP_FRAME_WIDTH))
        orig_h = int(cap.get(cv2.CAP_PROP_FRAME_HEIGHT))

        depth_files = sorted(glob.glob(str(Path(depth_dir) / "depth_*.npy")), key=lambda p: int(Path(p).stem.split("_")[1]))
        if not depth_files:
            raise FileNotFoundError(f"No depth files found in {depth_dir}")

        out_w = orig_w * 2 * self.upscale_factor
        out_h = orig_h * self.upscale_factor

        fourcc = cv2.VideoWriter_fourcc(*"mp4v")
        writer = cv2.VideoWriter(output_path, fourcc, fps, (out_w, out_h))

        frame_idx = 0
        total = min(int(cap.get(cv2.CAP_PROP_FRAME_COUNT)), len(depth_files))
        pbar = tqdm(total=total, desc="SBS generation")

        while True:
            ret, frame = cap.read()
            if not ret or frame_idx >= len(depth_files):
                break

            depth = np.load(depth_files[frame_idx])
            sbs_frame = self.generate_sbs_frame(frame, depth)
            writer.write(sbs_frame)
            frame_idx += 1
            pbar.update(1)

        cap.release()
        writer.release()
        pbar.close()
        logger.info(f"SBS video written: {output_path} ({out_w}x{out_h} @ {fps}fps)")
        return output_path

    def to_4k_h265(self, input_path: str, output_path: str) -> str:
        """Re-encode SBS video to H.265 4K."""
        cmd = [
            "ffmpeg",
            "-i", input_path,
            "-c:v", "libx265",
            "-crf", "23",
            "-preset", "medium",
            "-y",
            output_path,
        ]
        try:
            subprocess.run(cmd, check=True, capture_output=True)
            logger.info(f"4K H.265 re-encode: {output_path}")
        except FileNotFoundError:
            logger.warning("ffmpeg not found — skipping H.265 re-encode")
        except subprocess.CalledProcessError as e:
            logger.warning(f"ffmpeg failed: {e.stderr.decode()}")
        return output_path
