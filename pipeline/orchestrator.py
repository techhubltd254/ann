"""Master pipeline orchestrator.

Coordinates: analyze → depth → stereo/splat → refine → grade → deliver.
Runs standalone or via Redis queue for production scale.
"""

import json
import logging
import time
import os
from pathlib import Path
from datetime import datetime
from typing import Optional

from dotenv import load_dotenv

from analyzer import Analyzer, SceneAnalysis
from depth_worker import DepthWorker
from stereo_worker import StereoWorker
from splat_worker import SplatWorker
from refiner import Refiner
from resolver import DaVinciResolver
from flow_adapter import FlowAdapter

load_dotenv()

logger = logging.getLogger(__name__)


class Orchestrator:
    """Coordinates the full 2D→3D conversion pipeline."""

    def __init__(self, work_dir: str = "work"):
        self.work_dir = Path(work_dir)
        self.work_dir.mkdir(parents=True, exist_ok=True)
        self.analyzer = Analyzer()
        self.depth_worker = DepthWorker(model_size="small")
        self.stereo_worker = StereoWorker()
        self.splat_worker = SplatWorker(output_dir=str(self.work_dir / "splat"))
        self.refiner = Refiner()
        self.resolver = DaVinciResolver()
        self.flow = FlowAdapter()

    def run(
        self,
        video_path: str,
        output_dir: Optional[str] = None,
        pipeline: Optional[str] = None,
        depth_strength: Optional[float] = None,
        convergence: Optional[float] = None,
        skip_analysis: bool = False,
        skip_postpro: bool = False,
    ) -> dict:
        """Execute the full pipeline on a video.

        Args:
            video_path: Input 2D video.
            output_dir: Where to save outputs (default: work/orun_<timestamp>).
            pipeline: Force "sbs", "splat", or "hybrid". Auto-detect if None.
            depth_strength: Override analysis value.
            convergence: Override analysis value.
            skip_analysis: Use defaults instead of AI analysis.
            skip_postpro: Skip DaVinci and Google Flow stages.

        Returns:
            Result dict with paths to all outputs.
        """
        video_name = Path(video_path).stem
        if output_dir is None:
            ts = datetime.now().strftime("%Y%m%d_%H%M%S")
            output_dir = str(self.work_dir / f"orun_{video_name}_{ts}")
        Path(output_dir).mkdir(parents=True, exist_ok=True)

        timeline = {}
        result = {
            "video": video_path,
            "output_dir": output_dir,
            "status": "running",
            "steps": [],
        }

        # Step 1: Analyze
        if skip_analysis:
            analysis = SceneAnalysis({
                "scene_type": "walkthrough",
                "depth_strength": depth_strength or 1.5,
                "convergence": convergence or 0.3,
                "pipeline": pipeline or "sbs",
                "keyframe_indices": [0],
                "confidence": 1.0,
                "description": "Default (analysis skipped)",
            })
        else:
            t0 = time.time()
            analysis = self.analyzer.analyze(video_path)
            timeline["analysis_s"] = time.time() - t0
            result["steps"].append({"step": "analyze", "analysis": analysis.to_dict()})

        chosen_pipeline = pipeline or analysis.pipeline
        depth_strength = depth_strength or analysis.depth_strength
        convergence = convergence or analysis.convergence
        logger.info(f"Pipeline: {chosen_pipeline} | depth={depth_strength} | convergence={convergence}")

        depth_dir = str(Path(output_dir) / "depth")
        sbs_output = str(Path(output_dir) / f"{video_name}_SBS.mp4")
        h265_output = str(Path(output_dir) / f"{video_name}_4K3D.mp4")
        graded_output = str(Path(output_dir) / f"{video_name}_graded.mp4")
        branded_output = str(Path(output_dir) / f"{video_name}_final.mp4")

        # Step 2: Depth estimation
        t0 = time.time()
        depth_files = self.depth_worker.process_video(video_path, depth_dir)
        timeline["depth_s"] = time.time() - t0
        result["steps"].append({"step": "depth", "frames": len(depth_files)})
        logger.info(f"Depth: {len(depth_files)} maps in {timeline['depth_s']:.1f}s")

        # Step 3: Stereo generation
        if chosen_pipeline in ("sbs", "hybrid"):
            t0 = time.time()
            self.stereo_worker.depth_strength = depth_strength
            self.stereo_worker.convergence = convergence
            self.stereo_worker.process_video(video_path, depth_dir, sbs_output)
            timeline["stereo_s"] = time.time() - t0
            result["steps"].append({"step": "stereo", "output": sbs_output})

            t0 = time.time()
            self.stereo_worker.to_4k_h265(sbs_output, h265_output)
            timeline["h265_s"] = time.time() - t0
            result["steps"].append({"step": "h265_encode", "output": h265_output})
        else:
            result["steps"].append({"step": "stereo", "status": "skipped"})

        # Step 3b: Gaussian Splatting
        if chosen_pipeline in ("splat", "hybrid"):
            t0 = time.time()
            self.splat_worker.extract_frames(video_path)
            splat_result = self.splat_worker.generate_placeholder(video_path)
            timeline["splat_s"] = time.time() - t0
            result["steps"].append({"step": "splat", "output": splat_result})
        else:
            result["steps"].append({"step": "splat", "status": "skipped"})

        # Step 4: Refine keyframes
        if analysis.keyframe_indices:
            t0 = time.time()
            import cv2
            cap = cv2.VideoCapture(video_path)
            frames = []
            for idx in analysis.keyframe_indices:
                if idx < int(cap.get(cv2.CAP_PROP_FRAME_COUNT)):
                    cap.set(cv2.CAP_PROP_POS_FRAMES, idx)
                    ret, f = cap.read()
                    if ret:
                        frames.append(f)
            cap.release()
            if frames:
                ref_dir = str(Path(output_dir) / "refined_keyframes")
                ref_paths = self.refiner.enhance_frames(frames, ref_dir)
                timeline["refine_s"] = time.time() - t0
                result["steps"].append({"step": "refine", "keyframes": ref_paths})

        # Step 5: Post-production
        if not skip_postpro:
            source = h265_output if chosen_pipeline in ("sbs", "hybrid") else video_path
            t0 = time.time()
            self.resolver.color_grade(source, graded_output)
            timeline["grade_s"] = time.time() - t0
            result["steps"].append({"step": "color_grade", "output": graded_output})

            t0 = time.time()
            self.flow.generate_promo(f"Kenya exhibition: {analysis.description}")
            timeline["promo_s"] = time.time() - t0
            result["steps"].append({"step": "promo_clip"})

        result["status"] = "complete"
        result["timeline_s"] = timeline
        result["total_s"] = sum(timeline.values())
        logger.info(f"Pipeline complete in {result['total_s']:.1f}s — {output_dir}")
        return result


if __name__ == "__main__":
    logging.basicConfig(level=logging.INFO, format="%(asctime)s [%(levelname)s] %(name)s: %(message)s")
    import sys
    if len(sys.argv) < 2:
        print("Usage: python orchestrator.py <video_path> [--splat] [--hybrid] [--strength N]")
        sys.exit(1)

    video = sys.argv[1]
    kwargs = {}
    if "--splat" in sys.argv:
        kwargs["pipeline"] = "splat"
    if "--hybrid" in sys.argv:
        kwargs["pipeline"] = "hybrid"
    if "--strength" in sys.argv:
        idx = sys.argv.index("--strength") + 1
        kwargs["depth_strength"] = float(sys.argv[idx])

    orch = Orchestrator()
    result = orch.run(video, **kwargs)
    print(json.dumps(result, indent=2, default=str))
