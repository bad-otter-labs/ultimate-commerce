#!/usr/bin/env python3
from __future__ import annotations

import re
import struct
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "packages" / "ultimate-commerce-for-woocommerce"
README = PLUGIN / "readme.txt"
ASSETS = ROOT / "wordpress-org-assets"
EXPECTED = tuple(f"screenshot-{index}.png" for index in range(1, 5))
MAX_BYTES = 10 * 1024 * 1024


def fail(message: str) -> None:
    raise SystemExit(message)


def png_size(path: Path) -> tuple[int, int]:
    data = path.read_bytes()
    if len(data) < 24 or data[:8] != b"\x89PNG\r\n\x1a\n":
        fail(f"{path.relative_to(ROOT)} is not a valid PNG.")
    width, height = struct.unpack(">II", data[16:24])
    return int(width), int(height)


readme = README.read_text(encoding="utf-8")
match = re.search(
    r"^==\s*Screenshots\s*==\s*$([\s\S]*?)(?=^==\s*[^=]+\s*==\s*$|\Z)",
    readme,
    re.I | re.M,
)
if not match:
    fail("readme.txt is missing the Screenshots section.")

captions = re.findall(r"^\s*(\d+)\.\s+(.+?)\s*$", match.group(1), re.M)
expected_numbers = [str(index) for index in range(1, len(EXPECTED) + 1)]
if [number for number, _caption in captions] != expected_numbers:
    fail("Screenshot captions must be sequential 1-4 and match the staged assets one-to-one.")
if any(not caption.strip() for _number, caption in captions):
    fail("Screenshot captions must not be empty.")

actual = sorted(path.name for path in ASSETS.glob("screenshot-*.png"))
if actual != list(EXPECTED):
    fail(f"Expected exactly {list(EXPECTED)}, found {actual}.")

for filename in EXPECTED:
    path = ASSETS / filename
    size = path.stat().st_size
    if size <= 0 or size > MAX_BYTES:
        fail(f"{filename} must be non-empty and no larger than 10 MB.")
    width, height = png_size(path)
    if width < 700 or height < 400:
        fail(f"{filename} is unexpectedly small for directory presentation: {width}x{height}.")
    print(f"{filename}: {width}x{height}, {size} bytes")

leaked = sorted(path.relative_to(PLUGIN).as_posix() for path in PLUGIN.rglob("screenshot-*.png"))
if leaked:
    fail("WordPress.org directory screenshots must not ship in the plugin source/package: " + ", ".join(leaked))

print("WordPress.org screenshot assets and readme captions validated.")
