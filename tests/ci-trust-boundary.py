#!/usr/bin/env python3
from __future__ import annotations

from pathlib import Path
import re

ROOT = Path(__file__).resolve().parents[1]
WORKFLOWS = ROOT / ".github" / "workflows"
TRUSTED_PUBLISHERS = {
    "development-release.yml",
    "release.yml",
}

workflow_paths = sorted(
    list(WORKFLOWS.glob("*.yml")) + list(WORKFLOWS.glob("*.yaml"))
)

if not workflow_paths:
    raise SystemExit("No GitHub Actions workflows found.")

for path in workflow_paths:
    text = path.read_text(encoding="utf-8")

    if "pull_request_target:" in text:
        raise SystemExit(
            f"{path.name}: pull_request_target is forbidden for this repository trust boundary."
        )

    if "pull_request:" not in text:
        continue

    if "self-hosted" in text:
        raise SystemExit(
            f"{path.name}: pull-request code must not execute on a persistent self-hosted runner."
        )

    if "runs-on: ubuntu-latest" not in text:
        raise SystemExit(
            f"{path.name}: pull-request validation must use the isolated GitHub-hosted baseline."
        )

    if "permissions:" not in text or not re.search(
        r"(?m)^\s{2}contents:\s*read\s*$", text
    ):
        raise SystemExit(
            f"{path.name}: pull-request workflow must declare read-only contents permission."
        )

    write_permissions = re.findall(
        r"(?m)^\s{2}([A-Za-z0-9_-]+):\s*write\s*$", text
    )
    if write_permissions:
        joined = ", ".join(sorted(set(write_permissions)))
        raise SystemExit(
            f"{path.name}: pull-request workflow grants write permission: {joined}."
        )

for name in TRUSTED_PUBLISHERS:
    path = WORKFLOWS / name
    if not path.is_file():
        raise SystemExit(f"Trusted publisher workflow is missing: {name}")

    text = path.read_text(encoding="utf-8")
    if "pull_request:" in text or "pull_request_target:" in text:
        raise SystemExit(
            f"{name}: trusted publishing must never execute from a pull-request event."
        )
    if "self-hosted" not in text:
        raise SystemExit(
            f"{name}: trusted publisher unexpectedly left the controlled release runner."
        )

print("Ultimate Commerce public CI trust boundary validated.")
