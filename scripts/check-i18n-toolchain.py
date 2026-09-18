#!/usr/bin/env python3
from __future__ import annotations

import json
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
LOCK = ROOT / "tools" / "i18n" / "composer.lock"
MANIFEST = ROOT / "tools" / "i18n" / "composer.json"
ALLOWED = {"MIT", "BSD-3-Clause"}


def fail(message: str) -> None:
    raise SystemExit(message)


def load(path: Path) -> dict:
    value = json.loads(path.read_text(encoding="utf-8"))
    if not isinstance(value, dict):
        fail(f"{path} must contain a JSON object")
    return value


manifest = load(MANIFEST)
if manifest.get("license") != "GPL-2.0-or-later":
    fail("i18n development tool manifest must remain GPL-2.0-or-later.")
if (manifest.get("require-dev") or {}).get("wp-cli/wp-cli-bundle") != "2.12.0":
    fail("i18n extraction must remain pinned to WP-CLI bundle 2.12.0.")

lock = load(LOCK)
packages = list(lock.get("packages") or []) + list(lock.get("packages-dev") or [])
if not packages:
    fail("i18n composer.lock contains no packages.")

names = {str(package.get("name") or "") for package in packages}
for required in ("wp-cli/wp-cli-bundle", "wp-cli/wp-cli", "wp-cli/i18n-command"):
    if required not in names:
        fail(f"Pinned i18n toolchain is missing {required}.")

for package in packages:
    name = str(package.get("name") or "<unknown>")
    licenses = package.get("license")
    if not isinstance(licenses, list) or not licenses:
        fail(f"{name} does not declare a licence.")
    unknown = sorted(str(value) for value in licenses if str(value) not in ALLOWED)
    if unknown:
        fail(f"{name} declares unapproved i18n-tool licence(s): {', '.join(unknown)}")

print(f"i18n toolchain licences validated for {len(packages)} locked packages.")
