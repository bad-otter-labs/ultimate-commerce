#!/usr/bin/env python3
from __future__ import annotations

import argparse
import hashlib
import re
import shutil
import stat
import zipfile
from pathlib import Path, PurePosixPath

ROOT = Path(__file__).resolve().parents[1]
FIXED_TIME = (1980, 1, 1, 0, 0, 0)
SKIP_NAMES = {'.DS_Store'}
SKIP_SUFFIXES = {'.zip', '.sha256'}


def fail(message: str) -> None:
    raise SystemExit(message)


def plugin_version(path: Path) -> str:
    text = path.read_text(encoding='utf-8')
    match = re.search(r'^\s*\*\s*Version:\s*(\S+)\s*$', text, re.M)
    if not match:
        fail(f'Missing Version header in {path}')
    return match.group(1)


def add_bytes(zf: zipfile.ZipFile, arcname: str, data: bytes, mode: int) -> None:
    info = zipfile.ZipInfo(arcname, FIXED_TIME)
    info.compress_type = zipfile.ZIP_DEFLATED
    info.create_system = 3
    info.external_attr = (stat.S_IFREG | mode) << 16
    zf.writestr(info, data, compress_type=zipfile.ZIP_DEFLATED, compresslevel=9)


def iter_files(plugin_dir: Path):
    for path in sorted((p for p in plugin_dir.rglob('*') if p.is_file()), key=lambda p: p.as_posix()):
        rel = path.relative_to(plugin_dir)
        if path.name in SKIP_NAMES or path.suffix.lower() in SKIP_SUFFIXES:
            continue
        if any(part in {'.git', '.github', '__pycache__'} for part in rel.parts):
            continue
        yield path, rel


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument('--source', required=True)
    parser.add_argument('--output-dir', default='dist')
    args = parser.parse_args()

    plugin_dir = (ROOT / args.source).resolve()
    if not plugin_dir.is_dir():
        fail(f'Plugin source does not exist: {plugin_dir}')

    slug = plugin_dir.name
    main_file = plugin_dir / f'{slug}.php'
    if not main_file.is_file():
        fail(f'Missing plugin entry file {main_file.name}')

    version = plugin_version(main_file)
    out = (ROOT / args.output_dir).resolve()
    if out.exists():
        shutil.rmtree(out)
    out.mkdir(parents=True, exist_ok=True)
    package = out / f'{slug}-{version}.zip'

    with zipfile.ZipFile(package, 'w') as zf:
        for path, rel in iter_files(plugin_dir):
            arcname = str(PurePosixPath(slug) / PurePosixPath(rel.as_posix()))
            mode = 0o755 if path.stat().st_mode & stat.S_IXUSR else 0o644
            add_bytes(zf, arcname, path.read_bytes(), mode)

    digest = hashlib.sha256(package.read_bytes()).hexdigest()
    checksum = package.with_suffix(package.suffix + '.sha256')
    checksum.write_text(f'{digest}  {package.name}\n', encoding='ascii')
    print(f'SLUG={slug}')
    print(f'VERSION={version}')
    print(f'PACKAGE={package.name}')
    print(f'SHA256={digest}')
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
