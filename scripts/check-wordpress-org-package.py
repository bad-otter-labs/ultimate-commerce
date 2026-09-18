#!/usr/bin/env python3
from __future__ import annotations

import argparse
import json
import re
import stat
import zipfile
from pathlib import PurePosixPath

SLUG = 'ultimate-commerce-for-woocommerce'
MAIN = f'{SLUG}/{SLUG}.php'
README = f'{SLUG}/readme.txt'
COMPOSER = f'{SLUG}/composer.json'
POT = f'{SLUG}/languages/{SLUG}.pot'

FORBIDDEN_EXACT = {
    f'{SLUG}/module.json',
    f'{SLUG}/src/Updates/ManagedUpdates.php',
    f'{SLUG}/src/Updates/BadOtterClient.php',
}
FORBIDDEN_SUFFIXES = {
    '.zip', '.tar', '.gz', '.tgz', '.sql', '.sqlite', '.pem', '.key', '.p12', '.pfx', '.env',
}
TEXT_SUFFIXES = {
    '.php', '.json', '.txt', '.md', '.js', '.css', '.xml', '.yml', '.yaml', '.html', '.htm', '.pot', '.po',
}
BOUNDARY_PATTERNS = {
    'private Bad Otter API': re.compile(r'api\.badotter\.io', re.I),
    'third-party update header': re.compile(r'^\s*\*\s*Update URI:', re.I | re.M),
    'private managed updater': re.compile(r'\b(?:ManagedUpdates|BadOtterClient)\b'),
    'private package scheme': re.compile(r'uc-bad-otter://|badotter-managed://', re.I),
    'store-specific FishingClothing reference': re.compile(r'FishingClothing|fishingclothing\.co\.uk', re.I),
}
SECRET_PATTERNS = {
    'private key block': re.compile(r'-----BEGIN [A-Z0-9 ]*PRIVATE KEY-----'),
    'GitHub token': re.compile(r'\bgh[pousr]_[A-Za-z0-9]{20,}\b'),
    'AWS access key': re.compile(r'\bAKIA[0-9A-Z]{16}\b'),
    'Slack token': re.compile(r'\bxox[baprs]-[A-Za-z0-9-]{20,}\b'),
    'literal bearer credential': re.compile(r'Authorization\s*[:=]\s*["\']?Bearer\s+[A-Za-z0-9._~+/-]{20,}', re.I),
}


def fail(message: str) -> None:
    raise SystemExit(message)


def decode(zf: zipfile.ZipFile, name: str) -> str:
    try:
        return zf.read(name).decode('utf-8')
    except KeyError:
        fail(f'Missing required package file: {name}')
    except UnicodeDecodeError:
        fail(f'Required text file is not UTF-8: {name}')


def header(text: str, name: str) -> str:
    match = re.search(rf'^\s*\*\s*{re.escape(name)}:\s*(.+?)\s*$', text, re.M)
    if not match:
        fail(f'Missing {name} header')
    return match.group(1).strip()


def stable_tag(readme: str) -> str:
    match = re.search(r'^Stable tag:\s*(\S+)\s*$', readme, re.I | re.M)
    if not match:
        fail('readme.txt is missing Stable tag')
    return match.group(1).strip()


def safe_name(name: str) -> bool:
    path = PurePosixPath(name)
    return not path.is_absolute() and '..' not in path.parts and '\\' not in name


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument('package')
    args = parser.parse_args()

    with zipfile.ZipFile(args.package) as zf:
        infos = zf.infolist()
        names = [item.filename for item in infos]
        if not names:
            fail('Package is empty')
        if any(not safe_name(name) for name in names):
            fail('Package contains an unsafe archive path')
        if any(not name.startswith(SLUG + '/') for name in names):
            fail('Package contains files outside the canonical plugin root')
        if MAIN not in names or README not in names or POT not in names:
            fail('Package is missing its canonical main file, readme.txt or translation template')
        if FORBIDDEN_EXACT.intersection(names):
            fail(f'Package contains private distribution files: {sorted(FORBIDDEN_EXACT.intersection(names))}')

        for info in infos:
            name = info.filename
            if name.endswith('/'):
                continue
            mode = (info.external_attr >> 16) & 0o170000
            if mode == stat.S_IFLNK:
                fail(f'Package contains a symbolic link: {name}')
            suffix = PurePosixPath(name).suffix.lower()
            if suffix in FORBIDDEN_SUFFIXES:
                fail(f'Package contains forbidden release artifact/file type: {name}')
            if '/.git/' in name or '/.github/' in name or '/__pycache__/' in name:
                fail(f'Package contains development metadata: {name}')

        plugin = decode(zf, MAIN)
        readme = decode(zf, README)
        pot = decode(zf, POT)
        version = header(plugin, 'Version')
        if stable_tag(readme) != version:
            fail(f'Plugin Version {version} does not match readme Stable tag {stable_tag(readme)}')
        if header(plugin, 'Plugin Name') != 'Ultimate Commerce for WooCommerce':
            fail('Canonical plugin name changed unexpectedly')
        if header(plugin, 'Text Domain') != SLUG:
            fail('Canonical text domain changed unexpectedly')
        if re.search(r'^\s*\*\s*Update URI:', plugin, re.M):
            fail('WordPress.org Free package must not define Update URI')
        if '"X-Domain: ultimate-commerce-for-woocommerce\\n"' not in pot:
            fail('Packaged POT must declare the canonical translation domain')
        if '"POT-Creation-Date: \\n"' not in pot:
            fail('Packaged POT creation date must remain blank for deterministic regeneration')
        if 'msgid "Ultimate Commerce"' not in pot:
            fail('Packaged POT does not contain expected extracted runtime strings')

        required_sections = (
            'Description',
            'Installation',
            'Frequently Asked Questions',
            'Privacy',
            'External services',
            'Source and development',
            'Changelog',
        )
        for section in required_sections:
            pattern = '^==\\s*' + re.escape(section) + '\\s*==\\s*$'
            if not re.search(pattern, readme, re.I | re.M):
                fail(f'readme.txt is missing required submission section: {section}')

        for marker in (
            'does not contact any third-party service by default',
            'does not send usage telemetry',
            'https://github.com/bad-otter-labs/ultimate-commerce',
            'scripts/build-wordpress-org-package.py',
            'WordPress personal-data exporter and eraser',
        ):
            if marker.lower() not in readme.lower():
                fail(f'readme.txt is missing required disclosure/source marker: {marker}')

        if COMPOSER in names:
            composer = json.loads(decode(zf, COMPOSER))
            if composer.get('license') != 'GPL-2.0-or-later':
                fail('Packaged composer.json must remain GPL-2.0-or-later')

        for info in infos:
            name = info.filename
            if name.endswith('/') or PurePosixPath(name).suffix.lower() not in TEXT_SUFFIXES:
                continue
            raw = zf.read(name)
            if len(raw) > 2 * 1024 * 1024:
                fail(f'Unexpected oversized text file in package: {name}')
            try:
                text = raw.decode('utf-8')
            except UnicodeDecodeError:
                fail(f'Package text file is not UTF-8: {name}')
            for label, pattern in BOUNDARY_PATTERNS.items():
                if pattern.search(text):
                    fail(f'Package boundary violation ({label}) in {name}')
            for label, pattern in SECRET_PATTERNS.items():
                if pattern.search(text):
                    fail(f'Potential embedded secret ({label}) in {name}')

    print(f'WordPress.org package boundary validated: {args.package}')
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
