#!/usr/bin/env python3
from __future__ import annotations

import argparse
import hashlib
import json
import re
import shutil
import stat
import tempfile
import zipfile
from pathlib import Path, PurePosixPath

ROOT = Path(__file__).resolve().parents[1]
SLUG = 'ultimate-commerce-for-woocommerce'
DEV_PRODUCT = 'ultimate-commerce-for-woocommerce-dev'
FIXED_TIME = (1980, 1, 1, 0, 0, 0)
DEV_VERSION_RE = re.compile(r'^\d+\.\d+\.\d+-dev\.[1-9]\d*$')


def fail(message: str) -> None:
    raise SystemExit(message)


def add_bytes(zf: zipfile.ZipFile, arcname: str, data: bytes, mode: int) -> None:
    info = zipfile.ZipInfo(arcname, FIXED_TIME)
    info.compress_type = zipfile.ZIP_DEFLATED
    info.create_system = 3
    info.external_attr = (stat.S_IFREG | mode) << 16
    zf.writestr(info, data, compress_type=zipfile.ZIP_DEFLATED, compresslevel=9)


def canonical_version(entry: Path) -> str:
    text = entry.read_text(encoding='utf-8')
    match = re.search(r'^\s*\*\s*Version:\s*(\S+)\s*$', text, re.M)
    if not match:
        fail('Canonical plugin entry file has no Version header')
    return match.group(1)


def patch_entry(entry: Path, dev_version: str) -> None:
    text = entry.read_text(encoding='utf-8')
    text, header_count = re.subn(
        r'(^\s*\*\s*Version:\s*)\S+(\s*$)',
        rf'\g<1>{dev_version}\g<2>',
        text,
        count=1,
        flags=re.M,
    )
    if header_count != 1:
        fail('Could not patch development Version header exactly once')

    text, const_count = re.subn(
        r"define\('ULTIMATE_COMMERCE_VERSION',\s*'[^']+'\);",
        f"define('ULTIMATE_COMMERCE_VERSION', '{dev_version}');",
        text,
        count=1,
    )
    if const_count != 1:
        fail('Could not patch ULTIMATE_COMMERCE_VERSION exactly once')

    if 'Update URI:' in text:
        fail('Canonical source unexpectedly already has Update URI')
    text, uri_count = re.subn(
        r'(^\s*\*\s*Text Domain:\s*ultimate-commerce-for-woocommerce\s*$)',
        r'\1\n * Update URI: https://badotter.io/ultimate-commerce-for-woocommerce-dev',
        text,
        count=1,
        flags=re.M,
    )
    if uri_count != 1:
        fail('Could not inject development Update URI')

    marker = f"define('ULTIMATE_COMMERCE_VERSION', '{dev_version}');"
    replacement = (
        marker
        + "\ndefine('ULTIMATE_COMMERCE_DEVELOPMENT_BUILD', true);"
        + f"\ndefine('ULTIMATE_COMMERCE_DEVELOPMENT_PRODUCT', '{DEV_PRODUCT}');"
    )
    text = text.replace(marker, replacement, 1)

    bootstrap = "require_once ULTIMATE_COMMERCE_DIR . 'src/autoload.php';"
    hook = "\\BadOtter\\UltimateCommerce\\DevelopmentUpdates\\ManagedDevelopmentUpdates::hooks();"
    if hook in text:
        fail('Development updater hook already present in canonical source')
    if bootstrap not in text:
        fail('Could not locate canonical autoloader bootstrap')
    text = text.replace(bootstrap, bootstrap + '\n\n' + hook, 1)

    entry.write_text(text, encoding='utf-8')


def patch_readme(readme: Path, dev_version: str) -> None:
    text = readme.read_text(encoding='utf-8')
    text, count = re.subn(
        r'(^Stable tag:\s*)\S+(\s*$)',
        rf'\g<1>{dev_version}\g<2>',
        text,
        count=1,
        flags=re.M | re.I,
    )
    if count != 1:
        fail('Could not patch development Stable tag')
    readme.write_text(text, encoding='utf-8')


def write_manifest(plugin_dir: Path, dev_version: str, base_version: str) -> None:
    manifest = {
        'schema': 1,
        'id': 'core',
        'name': 'Ultimate Commerce for WooCommerce Development',
        'version': dev_version,
        'product': DEV_PRODUCT,
        'entitlement': None,
        'minimum_core': base_version,
        'category': 'commerce',
        'provider_type': 'wordpress-plugin',
        'managed_service': False,
        'capabilities': [
            'woocommerce-enhancement-platform',
            'development-managed-updates',
            'package-checksum-verification',
            'package-archive-identity-verification',
            'automatic-wordpress-update-discovery',
            'licence-free-update-delivery',
            'wordpress-org-handoff-capable',
        ],
        'description': (
            'Temporary Bad Otter development distribution for Ultimate Commerce for WooCommerce. '
            'The canonical WordPress.org source remains updater-free.'
        ),
        'channel': 'stable',
    }
    (plugin_dir / 'module.json').write_text(json.dumps(manifest, indent=2) + '\n', encoding='utf-8')


def iter_files(plugin_dir: Path):
    for path in sorted((p for p in plugin_dir.rglob('*') if p.is_file()), key=lambda p: p.as_posix()):
        rel = path.relative_to(plugin_dir)
        if any(part in {'.git', '.github', '__pycache__'} for part in rel.parts):
            continue
        if path.suffix.lower() in {'.zip', '.sha256'}:
            continue
        yield path, rel


def validate_built_tree(plugin_dir: Path, dev_version: str) -> None:
    entry = (plugin_dir / f'{SLUG}.php').read_text(encoding='utf-8')
    required = (
        f'Version: {dev_version}',
        'Update URI: https://badotter.io/ultimate-commerce-for-woocommerce-dev',
        "define('ULTIMATE_COMMERCE_DEVELOPMENT_BUILD', true);",
        'ManagedDevelopmentUpdates::hooks();',
    )
    for value in required:
        if value not in entry:
            fail(f'Development package entry file is missing {value!r}')

    for rel in (
        'src/DevelopmentUpdates/BadOtterDevelopmentClient.php',
        'src/DevelopmentUpdates/ManagedDevelopmentUpdates.php',
        'module.json',
    ):
        if not (plugin_dir / rel).is_file():
            fail(f'Development package is missing {rel}')

    manifest = json.loads((plugin_dir / 'module.json').read_text(encoding='utf-8'))
    if manifest.get('product') != DEV_PRODUCT or manifest.get('version') != dev_version:
        fail('Development manifest identity/version mismatch')
    capabilities = set(manifest.get('capabilities') or [])
    if 'licence-free-update-delivery' not in capabilities:
        fail('Development manifest must declare licence-free update delivery')


def validate_canonical_boundary(source: Path) -> None:
    entry = (source / f'{SLUG}.php').read_text(encoding='utf-8')
    if 'Update URI:' in entry or 'ULTIMATE_COMMERCE_DEVELOPMENT_BUILD' in entry or 'ManagedDevelopmentUpdates::hooks' in entry:
        fail('Canonical Free source contains development updater markers')
    if (source / 'src/DevelopmentUpdates').exists() or (source / 'module.json').exists():
        fail('Canonical Free source contains development distribution files')


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument('--source', default='packages/ultimate-commerce-for-woocommerce')
    parser.add_argument('--overlay', default='development/free-updater')
    parser.add_argument('--version', required=True)
    parser.add_argument('--output-dir', default='dist-development')
    args = parser.parse_args()

    if not DEV_VERSION_RE.fullmatch(args.version):
        fail('Development version must look like 0.2.0-dev.1')

    source = (ROOT / args.source).resolve()
    overlay = (ROOT / args.overlay).resolve()
    if not source.is_dir() or source.name != SLUG:
        fail(f'Canonical Free source not found at {source}')
    if not overlay.is_dir():
        fail(f'Development updater overlay not found at {overlay}')

    validate_canonical_boundary(source)
    base_version = canonical_version(source / f'{SLUG}.php')
    version_prefix = re.match(r'^(\d+\.\d+\.\d+)-dev\.', args.version)
    if version_prefix and version_prefix.group(1) != base_version:
        fail(f'Development version {args.version} must be based on canonical version {base_version}')

    out = (ROOT / args.output_dir).resolve()
    if out.exists():
        shutil.rmtree(out)
    out.mkdir(parents=True, exist_ok=True)

    with tempfile.TemporaryDirectory(prefix='uc-free-dev-') as tmp:
        plugin_dir = Path(tmp) / SLUG
        shutil.copytree(source, plugin_dir)
        dev_dir = plugin_dir / 'src' / 'DevelopmentUpdates'
        dev_dir.mkdir(parents=True, exist_ok=True)
        shutil.copy2(overlay / 'BadOtterDevelopmentClient.php', dev_dir / 'BadOtterDevelopmentClient.php')
        shutil.copy2(overlay / 'ManagedDevelopmentUpdates.php', dev_dir / 'ManagedDevelopmentUpdates.php')

        patch_entry(plugin_dir / f'{SLUG}.php', args.version)
        patch_readme(plugin_dir / 'readme.txt', args.version)
        write_manifest(plugin_dir, args.version, base_version)
        validate_built_tree(plugin_dir, args.version)

        package = out / f'{SLUG}-{args.version}.zip'
        with zipfile.ZipFile(package, 'w') as zf:
            for path, rel in iter_files(plugin_dir):
                arcname = str(PurePosixPath(SLUG) / PurePosixPath(rel.as_posix()))
                mode = 0o755 if path.stat().st_mode & stat.S_IXUSR else 0o644
                add_bytes(zf, arcname, path.read_bytes(), mode)

    digest = hashlib.sha256(package.read_bytes()).hexdigest()
    checksum = package.with_suffix(package.suffix + '.sha256')
    checksum.write_text(f'{digest}  {package.name}\n', encoding='ascii')

    print(f'PRODUCT={DEV_PRODUCT}')
    print(f'VERSION={args.version}')
    print(f'PACKAGE={package.name}')
    print(f'SHA256={digest}')
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
