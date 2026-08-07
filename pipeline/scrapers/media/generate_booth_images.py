"""Generate booth design images using OpenAI DALL-E 3"""

import os
import json
import time
from pathlib import Path

from openai import OpenAI

SHOTS = [
    {
        "name": "01_booth_exterior",
        "prompt": "Cinematic wide shot of a modern 6m x 4m exhibition booth at a busy trade show. Three seamless curved LED walls showing Kenya flag and savanna landscapes. Black aluminium frame, warm acacia wood accents, Maasai red fabric. A curved LED entry arch pulses with golden light. Premium expo lighting, shallow depth of field, 4K, cinematic warm amber tones, photorealistic.",
    },
    {
        "name": "02_visitor_entering",
        "prompt": "Interior view from inside a futuristic exhibition booth. A visitor stands centre, surrounded by 270-degree curved LED walls displaying golden Maasai Mara savanna at sunset. Circular glass touch table glows at centre with Kenya map interface. Raised dark acrylic floor with warm underglow. Acacia wood slat wall with Maasai shuka fabric. Photorealistic, cinematic lighting, ultra-detailed.",
    },
    {
        "name": "03_interactive_table",
        "prompt": "Top-down close-up of hands touching a glossy circular interactive touch table at a Kenya exhibition booth. Table screen shows a map of Kenya with 47 glowing counties in gold and blue UI. Fingers tapping Nakuru county. African modern design context, warm tungsten lighting reflecting off glass surface, photorealistic, 4K.",
    },
    {
        "name": "04_immersive_mara",
        "prompt": "Wide interior of a 270-degree LED exhibition booth showing wildebeest crossing Mara River on all screens. Golden sunlight fills the space. A visitor stands centre, arms slightly out, immersed. Haptic floor panels visible as subtle grid. Dust particles in warm air. Cinematic, ultra-realistic, rich warm colours, emotional, National Geographic quality.",
    },
    {
        "name": "05_lion_encounter",
        "prompt": "Inside a curved LED booth as a massive lion walks directly toward camera on all three screens. The visitor in foreground flinches slightly, eyes wide. Warm orange glow from savanna sunset. Dust haze in air. Haptic floor vibration implied. Cinematic, tense, exciting, hyper-realistic, IMAX documentary quality, 4K.",
    },
]

OUTPUT_DIR = Path("booth_images")
OUTPUT_DIR.mkdir(exist_ok=True)

client = OpenAI(api_key=os.environ.get("OPENAI_API_KEY", input("OpenAI API key: ").strip()))

def generate(name: str, prompt: str):
    print(f"\n[{name}] Generating...")
    resp = client.images.generate(
        model="dall-e-3",
        prompt=prompt,
        size="1792x1024",
        quality="hd",
        n=1,
    )
    url = resp.data[0].url
    path = OUTPUT_DIR / f"{name}.png"
    import httpx
    r = httpx.get(url, timeout=60)
    path.write_bytes(r.content)
    print(f"  Saved: {path}")
    return path

for shot in SHOTS:
    generate(shot["name"], shot["prompt"])
    time.sleep(3)

print(f"\nDone. {len(SHOTS)} images saved to {OUTPUT_DIR}/")
