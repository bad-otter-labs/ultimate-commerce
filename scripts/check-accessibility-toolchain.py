#!/usr/bin/env python3
from __future__ import annotations

import json
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
MANIFEST = ROOT / "tools" / "accessibility" / "package.json"
LOCK = ROOT / "tools" / "accessibility" / "package-lock.json"

EXPECTED = {
    "@axe-core/playwright": "4.13.0",
    "@playwright/test": "1.63.0",
}
ALLOWED_LICENSES = {"Apache-2.0", "MPL-2.0"}


def fail(message: str) -> None:
    raise SystemExit(message)


def read_json(path: Path) -> dict:
    try:
        value = json.loads(path.read_text(encoding="utf-8"))
    except (OSError, json.JSONDecodeError) as exc:
        fail(f"Could not read {path.relative_to(ROOT)}: {exc}")
    if not isinstance(value, dict):
        fail(f"{path.relative_to(ROOT)} must contain a JSON object.")
    return value


manifest = read_json(MANIFEST)
if manifest.get("private") is not True:
    fail("Accessibility tooling package must remain private.")
if manifest.get("license") != "GPL-2.0-or-later":
    fail("Accessibility tooling manifest must remain GPL-2.0-or-later.")
if manifest.get("devDependencies") != EXPECTED:
    fail("Accessibility direct dependencies must remain exactly pinned.")

lock = read_json(LOCK)
if lock.get("lockfileVersion") != 3:
    fail("Accessibility package-lock.json must remain npm lockfileVersion 3.")

packages = lock.get("packages")
if not isinstance(packages, dict):
    fail("Accessibility package-lock.json is missing packages metadata.")

root = packages.get("")
if not isinstance(root, dict) or root.get("devDependencies") != EXPECTED:
    fail("Accessibility lockfile root dependency pins do not match package.json.")

required = {
    "node_modules/@axe-core/playwright": ("4.13.0", "MPL-2.0"),
    "node_modules/@playwright/test": ("1.63.0", "Apache-2.0"),
    "node_modules/axe-core": ("4.13.0", "MPL-2.0"),
    "node_modules/playwright": ("1.63.0", "Apache-2.0"),
    "node_modules/playwright-core": ("1.63.0", "Apache-2.0"),
}

for path, (version, license_name) in required.items():
    package = packages.get(path)
    if not isinstance(package, dict):
        fail(f"Accessibility lockfile is missing {path}.")
    if package.get("version") != version:
        fail(f"{path} version changed from pinned {version}.")
    if package.get("license") != license_name or license_name not in ALLOWED_LICENSES:
        fail(f"{path} has unexpected licence metadata.")

unexpected = sorted(
    path
    for path in packages
    if path and path.startswith("node_modules/") and path not in required
)
if unexpected:
    fail("Accessibility lockfile gained unreviewed packages: " + ", ".join(unexpected))

print("Accessibility browser toolchain pins and licences validated.")
