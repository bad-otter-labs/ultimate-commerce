#!/usr/bin/env python3
from __future__ import annotations

import argparse
import hashlib
import json
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


def source_manifest(plugin_dir: Path) -> dict:
    manifest_path = plugin_dir / 'module.json'
    if not manifest_path.is_file():
        fail(f'Missing canonical module.json in {plugin_dir}')
    manifest = json.loads(manifest_path.read_text(encoding='utf-8'))
    product = str(manifest.get('product') or '').strip()
    if not product or product != plugin_dir.name:
        fail('Manifest product must match plugin directory')
    if manifest.get('id') != 'core':
        fail('Manifest id must be core')
    main = plugin_dir / f'{product}.php'
    if not main.is_file():
        fail(f'Missing plugin entry file {main.name}')
    version = plugin_version(main)
    if str(manifest.get('version') or '') != version:
        fail('Manifest/plugin version mismatch')
    if manifest.get('entitlement') is not None:
        fail('Source entitlement must remain null for licence-free delivery')
    capabilities = set(manifest.get('capabilities') or [])
    if 'licence-free-update-delivery' not in capabilities:
        fail('Licence-free source manifest capability is required')
    return manifest


def release_manifest(plugin_dir: Path) -> bytes:
    manifest = dict(source_manifest(plugin_dir))
    capabilities = list(manifest.get('capabilities') or [])
    product = str(manifest['product'])
    module = str(manifest['id'])
    manifest['entitlement'] = f'{product}-{module}'
    if 'free-core-updates' not in capabilities:
        capabilities.append('free-core-updates')
    capabilities = [item for item in capabilities if item != 'entitlement-controlled-delivery']
    manifest['capabilities'] = capabilities
    return (json.dumps(manifest, indent=2, ensure_ascii=False) + '\n').encode('utf-8')


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
    manifest = source_manifest(plugin_dir)
    product = str(manifest['product'])
    version = str(manifest['version'])

    out = (ROOT / args.output_dir).resolve()
    if out.exists():
        shutil.rmtree(out)
    out.mkdir(parents=True, exist_ok=True)
    package = out / f'{product}-{version}.zip'
    published_manifest = release_manifest(plugin_dir)

    with zipfile.ZipFile(package, 'w') as zf:
        for path, rel in iter_files(plugin_dir):
            arcname = str(PurePosixPath(product) / PurePosixPath(rel.as_posix()))
            mode = 0o755 if path.stat().st_mode & stat.S_IXUSR else 0o644
            data = published_manifest if rel.as_posix() == 'module.json' else path.read_bytes()
            add_bytes(zf, arcname, data, mode)

    digest = hashlib.sha256(package.read_bytes()).hexdigest()
    checksum = package.with_suffix(package.suffix + '.sha256')
    checksum.write_text(f'{digest}  {package.name}\n', encoding='ascii')
    print(f'PRODUCT={product}')
    print(f'VERSION={version}')
    print(f'PACKAGE={package.name}')
    print(f'SHA256={digest}')
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
