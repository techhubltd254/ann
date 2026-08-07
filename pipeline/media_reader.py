"""Image and video reader with OCR, visualization, and numpy processing.

Reads, analyzes, and extracts data from images and video files.
Integrates with the KICC pipeline for county content processing.
"""

import io
import logging
import os
from pathlib import Path
from typing import Optional

import cv2
import matplotlib.pyplot as plt
import numpy as np
from PIL import Image

logger = logging.getLogger(__name__)

# Optional OCR
try:
    import pytesseract

    OCR_AVAILABLE = True
except ImportError:
    OCR_AVAILABLE = False
    logger.warning("pytesseract not installed — OCR disabled")


class ImageReader:
    """Load, inspect, and extract data from images."""

    SUPPORTED_EXTS = {".jpg", ".jpeg", ".png", ".tiff", ".bmp", ".webp"}

    def __init__(self, path: str | Path):
        self.path = Path(path)
        if not self.path.exists():
            raise FileNotFoundError(f"Image not found: {self.path}")
        if self.path.suffix.lower() not in self.SUPPORTED_EXTS:
            raise ValueError(f"Unsupported format: {self.path.suffix}")
        self._cv = None
        self._pil = None

    @property
    def cv(self) -> np.ndarray:
        """OpenCV BGR array."""
        if self._cv is None:
            self._cv = cv2.imread(str(self.path))
            if self._cv is None:
                raise ValueError(f"OpenCV failed to read: {self.path}")
        return self._cv

    @property
    def pil(self) -> Image.Image:
        """PIL Image (RGB)."""
        if self._pil is None:
            self._pil = Image.open(self.path).convert("RGB")
        return self._pil

    @property
    def rgb(self) -> np.ndarray:
        """RGB numpy array (matplotlib-compatible)."""
        return cv2.cvtColor(self.cv, cv2.COLOR_BGR2RGB)

    @property
    def shape(self) -> tuple:
        return self.cv.shape

    def resize(self, width: int, height: int) -> np.ndarray:
        return cv2.resize(self.cv, (width, height), interpolation=cv2.INTER_AREA)

    def grayscale(self) -> np.ndarray:
        return cv2.cvtColor(self.cv, cv2.COLOR_BGR2GRAY)

    def ocr(self, lang: str = "eng") -> str:
        """Extract text via Tesseract OCR."""
        if not OCR_AVAILABLE:
            raise RuntimeError("pytesseract not installed — run: pip install pytesseract")
        gray = self.grayscale()
        _, thresh = cv2.threshold(gray, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)
        return pytesseract.image_to_string(thresh, lang=lang).strip()

    def ocr_regions(self, lang: str = "eng") -> list[dict]:
        """Detect text regions with bounding boxes."""
        if not OCR_AVAILABLE:
            raise RuntimeError("pytesseract not installed")
        gray = self.grayscale()
        data = pytesseract.image_to_data(gray, lang=lang, output_type=pytesseract.Output.DICT)
        results = []
        for i in range(len(data["text"])):
            text = data["text"][i].strip()
            if not text:
                continue
            results.append({
                "text": text,
                "confidence": int(data["conf"][i]),
                "x": data["left"][i],
                "y": data["top"][i],
                "w": data["width"][i],
                "h": data["height"][i],
            })
        return results

    def show(self, title: Optional[str] = None, figsize: tuple = (12, 8)):
        """Display image with matplotlib."""
        plt.figure(figsize=figsize)
        plt.imshow(self.rgb)
        plt.title(title or self.path.name)
        plt.axis("off")
        plt.tight_layout()
        plt.show()

    def to_bytes(self, fmt: str = "png") -> bytes:
        buf = io.BytesIO()
        self.pil.save(buf, format=fmt)
        return buf.getvalue()

    @staticmethod
    def load_batch(paths: list[str | Path]) -> list["ImageReader"]:
        return [ImageReader(p) for p in paths]

    def __repr__(self) -> str:
        return f"ImageReader({self.path.name}, {self.shape[1]}x{self.shape[0]})"


class VideoReader:
    """Load, sample, and extract data from video files."""

    SUPPORTED_EXTS = {".mp4", ".avi", ".mov", ".mkv", ".webm"}

    def __init__(self, path: str | Path):
        self.path = Path(path)
        if not self.path.exists():
            raise FileNotFoundError(f"Video not found: {self.path}")
        if self.path.suffix.lower() not in self.SUPPORTED_EXTS:
            raise ValueError(f"Unsupported format: {self.path.suffix}")
        self._cap = cv2.VideoCapture(str(self.path))
        if not self._cap.isOpened():
            raise ValueError(f"OpenCV failed to open: {self.path}")
        self._total_frames = int(self._cap.get(cv2.CAP_PROP_FRAME_COUNT))
        self.fps = self._cap.get(cv2.CAP_PROP_FPS)
        self.width = int(self._cap.get(cv2.CAP_PROP_FRAME_WIDTH))
        self.height = int(self._cap.get(cv2.CAP_PROP_FRAME_HEIGHT))
        self.duration_sec = self._total_frames / self.fps if self.fps > 0 else 0

    @property
    def total_frames(self) -> int:
        return self._total_frames

    def read_frame(self, index: int) -> Optional[np.ndarray]:
        """Read a specific frame index. Returns BGR numpy array or None."""
        if index < 0 or index >= self._total_frames:
            raise IndexError(f"Frame {index} out of range (0-{self._total_frames - 1})")
        self._cap.set(cv2.CAP_PROP_POS_FRAMES, index)
        ret, frame = self._cap.read()
        return frame if ret else None

    def read_frame_rgb(self, index: int) -> Optional[np.ndarray]:
        frame = self.read_frame(index)
        if frame is not None:
            return cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)
        return None

    def sample_frames(self, n: int = 5) -> list[dict]:
        """Sample N evenly-spaced frames with metadata."""
        if n < 1:
            return []
        indices = np.linspace(0, self._total_frames - 1, n, dtype=int).tolist()
        frames = []
        for idx in indices:
            frame = self.read_frame(idx)
            if frame is not None:
                frames.append({
                    "index": idx,
                    "time_sec": round(idx / self.fps, 2) if self.fps > 0 else 0,
                    "bgr": frame,
                    "rgb": cv2.cvtColor(frame, cv2.COLOR_BGR2RGB),
                })
        return frames

    def extract_keyframes(self, method: str = "diff", threshold: float = 30.0) -> list[dict]:
        """Detect keyframes by frame-difference or histogram comparison."""
        keyframes = []
        prev = None
        for i in range(self._total_frames):
            frame = self.read_frame(i)
            if frame is None:
                break
            if method == "diff":
                gray = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
                if prev is not None:
                    diff = cv2.absdiff(gray, prev).mean()
                    if diff > threshold:
                        keyframes.append({"index": i, "time_sec": i / self.fps, "bgr": frame})
                prev = gray
            else:
                if prev is not None:
                    hist = cv2.calcHist([frame], [0], None, [256], [0, 256])
                    prev_hist = cv2.calcHist([prev], [0], None, [256], [0, 256])
                    diff = cv2.compareHist(hist, prev_hist, cv2.HISTCMP_CHISQR)
                    if diff > threshold:
                        keyframes.append({"index": i, "time_sec": i / self.fps, "bgr": frame})
                prev = frame
        return keyframes

    def extract_audio_metadata(self) -> dict:
        """Extract basic audio metadata from the video container (no decoding)."""
        return {
            "channels": self._cap.get(cv2.CAP_PROP_AUDIO_CHANNELS),
            "sample_rate": self._cap.get(cv2.CAP_PROP_AUDIO_SAMPLES_PER_SEC),
        }

    def show_frame(self, index: int, title: Optional[str] = None, figsize: tuple = (12, 8)):
        """Display a single video frame with matplotlib."""
        frame = self.read_frame_rgb(index)
        if frame is None:
            logger.error(f"Cannot show frame {index}")
            return
        plt.figure(figsize=figsize)
        plt.imshow(frame)
        plt.title(title or f"{self.path.name} — frame {index} @ {index / self.fps:.1f}s")
        plt.axis("off")
        plt.tight_layout()
        plt.show()

    def make_thumbnail(self, index: int = 0, size: tuple = (320, 240)) -> np.ndarray:
        """Generate a thumbnail from a video frame."""
        frame = self.read_frame(index)
        if frame is None:
            raise ValueError(f"Cannot read frame {index}")
        return cv2.resize(frame, size, interpolation=cv2.INTER_AREA)

    def to_animated_gif(self, out_path: str | Path, n_frames: int = 10, duration_ms: int = 500):
        """Export evenly-spaced frames as an animated GIF."""
        frames_pil = []
        indices = np.linspace(0, self._total_frames - 1, n_frames, dtype=int)
        for idx in indices:
            frame = self.read_frame_rgb(idx)
            if frame is not None:
                frames_pil.append(Image.fromarray(frame))
        if frames_pil:
            frames_pil[0].save(
                str(out_path),
                save_all=True,
                append_images=frames_pil[1:],
                duration=duration_ms,
                loop=0,
            )
            logger.info(f"GIF saved: {out_path}")

    def release(self):
        self._cap.release()

    def __enter__(self):
        return self

    def __exit__(self, *args):
        self.release()

    def __repr__(self) -> str:
        return f"VideoReader({self.path.name}, {self.width}x{self.height}, {self.total_frames}frames, {self.duration_sec:.1f}s)"


def overlay_text(
    image: np.ndarray,
    text: str,
    org: tuple = (30, 60),
    font_scale: float = 1.2,
    color: tuple = (255, 255, 255),
    thickness: int = 2,
) -> np.ndarray:
    """Draw text on a BGR image with a dark background bar for readability."""
    out = image.copy()
    (tw, th), _ = cv2.getTextSize(text, cv2.FONT_HERSHEY_SIMPLEX, font_scale, thickness)
    x, y = org
    cv2.rectangle(out, (x - 10, y - th - 10), (x + tw + 10, y + 10), (0, 0, 0), -1)
    cv2.putText(out, text, (x, y), cv2.FONT_HERSHEY_SIMPLEX, font_scale, color, thickness)
    return out


def batch_process_images(
    paths: list[str | Path],
    fn,
    output_dir: str | Path = "processed",
) -> list[Path]:
    """Apply a function to each image and save results."""
    out_dir = Path(output_dir)
    out_dir.mkdir(exist_ok=True)
    results = []
    for p in paths:
        reader = ImageReader(p)
        result = fn(reader)
        out_path = out_dir / f"processed_{reader.path.name}"
        cv2.imwrite(str(out_path), result)
        results.append(out_path)
        logger.info(f"Processed: {reader.path.name} -> {out_path}")
    return results


def frames_to_video(
    frames: list[np.ndarray],
    out_path: str | Path,
    fps: float = 30.0,
    codec: str = "mp4v",
):
    """Write a list of BGR frames to a video file."""
    if not frames:
        raise ValueError("No frames to write")
    h, w = frames[0].shape[:2]
    fourcc = cv2.VideoWriter_fourcc(*codec)
    writer = cv2.VideoWriter(str(out_path), fourcc, fps, (w, h))
    for frame in frames:
        writer.write(frame)
    writer.release()
    logger.info(f"Video saved: {out_path} ({len(frames)} frames @ {fps}fps)")


if __name__ == "__main__":
    logging.basicConfig(level=logging.INFO)
    print("media_reader.py — available components:")
    print(f"  ImageReader   — load/ocr/process images  (OCR={'✓' if OCR_AVAILABLE else '✗'})")
    print(f"  VideoReader   — load/sample/extract video")
    print(f"  overlay_text  — annotate images")
    print(f"  batch_process_images — bulk image pipeline")
    print(f"  frames_to_video      — export frame list to .mp4")
