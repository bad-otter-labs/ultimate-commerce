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
SAFE_RUNNER = (
    "runs-on: ${{ github.event_name == 'pull_request' "
    "&& github.event.pull_request.head.repo.full_name != github.repository "
    "&& 'ubuntu-latest' || 'badotter' }}"
)

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

    if SAFE_RUNNER not in text:
        raise SystemExit(
            f"{path.name}: pull-request runner must route cross-repository heads to ubuntu-latest "
            "and reserve the Bad Otter runner for same-repository branches."
        )

    if re.search(r"(?m)^\s*uses:\s*[^#\n]+@(main|master|HEAD)\s*$", text):
        raise SystemExit(
            f"{path.name}: pull-request workflow contains an unpinned mutable action branch."
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
    if "self-hosted" not in text or "badotter" not in text:
        raise SystemExit(
            f"{name}: trusted publisher unexpectedly left the controlled release runner."
        )

print("Ultimate Commerce public CI trust boundary validated.")
