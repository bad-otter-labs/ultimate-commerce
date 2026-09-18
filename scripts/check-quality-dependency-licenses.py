#!/usr/bin/env python3
from __future__ import annotations

import json
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
LOCK = ROOT / "composer.lock"
TOOLCHAIN = ROOT / "composer.json"
FREE_COMPOSER = ROOT / "packages" / "ultimate-commerce-for-woocommerce" / "composer.json"

ALLOWED_DEPENDENCY_LICENSES = {
    "MIT",
    "BSD-3-Clause",
    "LGPL-3.0-or-later",
}


def fail(message: str) -> None:
    raise SystemExit(message)


def read_json(path: Path) -> dict:
    try:
        value = json.loads(path.read_text(encoding="utf-8"))
    except (OSError, json.JSONDecodeError) as exc:
        fail(f"Could not read JSON from {path.relative_to(ROOT)}: {exc}")
    if not isinstance(value, dict):
        fail(f"{path.relative_to(ROOT)} must contain a JSON object")
    return value


toolchain = read_json(TOOLCHAIN)
if toolchain.get("license") != "GPL-2.0-or-later":
    fail("Root development toolchain must remain GPL-2.0-or-later.")

lock = read_json(LOCK)
packages = list(lock.get("packages") or []) + list(lock.get("packages-dev") or [])
if not packages:
    fail("composer.lock must contain the pinned quality dependencies.")

for package in packages:
    if not isinstance(package, dict):
        fail("composer.lock contains an invalid package entry.")
    name = str(package.get("name") or "<unknown>")
    version = str(package.get("version") or "<unknown>")
    licenses = package.get("license")
    if not isinstance(licenses, list) or not licenses:
        fail(f"{name} {version} does not declare a dependency licence.")
    unsupported = sorted(
        str(value)
        for value in licenses
        if str(value) not in ALLOWED_DEPENDENCY_LICENSES
    )
    if unsupported:
        fail(
            f"{name} {version} declares unapproved dependency licence(s): "
            + ", ".join(unsupported)
        )

free = read_json(FREE_COMPOSER)
if free.get("license") != "GPL-2.0-or-later":
    fail("Canonical Free package composer licence must remain GPL-2.0-or-later.")

runtime_require = free.get("require") or {}
if not isinstance(runtime_require, dict):
    fail("Canonical Free composer require section must be an object.")

third_party_runtime = sorted(
    name
    for name in runtime_require
    if name != "php" and not name.startswith("ext-")
)
if third_party_runtime:
    fail(
        "Canonical Free package gained a Composer runtime dependency that must be "
        "reviewed explicitly before WordPress.org release: "
        + ", ".join(third_party_runtime)
    )

print(
    f"Quality dependency licences validated for {len(packages)} locked development packages; "
    "canonical Free has no third-party Composer runtime dependency."
)
