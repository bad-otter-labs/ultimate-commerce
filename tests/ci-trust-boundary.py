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
FAST_SELF_HOSTED_WORKFLOWS = {
    "ci-trust-boundary.yml",
    "ci-draft.yml",
}
HEAVY_SELF_HOSTED_WORKFLOWS = {
    "accessibility.yml",
    "wordpress-org.yml",
    "ci-full.yml",
}
HOSTED_RUNNER = "runs-on: ubuntu-latest"
FORK_SAFE_FAST_RUNNER = (
    "runs-on: ${{ github.event_name == 'pull_request' "
    "&& github.event.pull_request.head.repo.full_name != github.repository "
    "&& 'ubuntu-latest' || fromJSON('[\"self-hosted\",\"Linux\",\"X64\",\"badotter\",\"ci-fast\"]') }}"
)
FORK_SAFE_HEAVY_RUNNER = (
    "runs-on: ${{ github.event_name == 'pull_request' "
    "&& github.event.pull_request.head.repo.full_name != github.repository "
    "&& 'ubuntu-latest' || fromJSON('[\"self-hosted\",\"Linux\",\"X64\",\"badotter\",\"ci\"]') }}"
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

    path_entries = re.findall(r"(?m)^\s+- ['\"]([^'\"]+)['\"]\s*$", text)
    for entry in path_entries:
        if not any(char in entry for char in "*?[") and (ROOT / entry).is_dir():
            raise SystemExit(
                f"{path.name}: path filter names directory without recursive glob: {entry}"
            )

    if "pull_request:" not in text:
        continue

    expected_runner = (
        FORK_SAFE_FAST_RUNNER
        if path.name in FAST_SELF_HOSTED_WORKFLOWS
        else FORK_SAFE_HEAVY_RUNNER
        if path.name in HEAVY_SELF_HOSTED_WORKFLOWS
        else HOSTED_RUNNER
    )
    runner_lines = [
        line.strip() for line in text.splitlines()
        if line.strip().startswith("runs-on:")
    ]
    if not runner_lines or any(line != expected_runner for line in runner_lines):
        raise SystemExit(
            f"{path.name}: runner does not match the approved hosted/fast/heavy trust boundary."
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
    if HOSTED_RUNNER not in text:
        raise SystemExit(
            f"{name}: trusted publisher must use isolated GitHub-hosted capacity."
        )

print("Ultimate Commerce public CI trust boundary validated.")
