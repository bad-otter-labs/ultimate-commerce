#!/usr/bin/env bash
set -euo pipefail

: "${WP_VERSION:?WP_VERSION is required}"
: "${PHP_VERSION:?PHP_VERSION is required}"
: "${WC_VERSION:?WC_VERSION is required}"
: "${WC_SHA256:?WC_SHA256 is required}"

if [[ "$PHP_VERSION" != "8.1" && "$PHP_VERSION" != "8.3" ]]; then
  echo "Unsupported compatibility-test PHP version: $PHP_VERSION" >&2
  exit 1
fi

workspace="${GITHUB_WORKSPACE:-$(pwd)}"
run_id="${GITHUB_RUN_ID:-local}"
attempt="${GITHUB_RUN_ATTEMPT:-1}"
slug="wp-${WP_VERSION//./-}-wc-${WC_VERSION//./-}-php-${PHP_VERSION//./-}"
root="${RUNNER_TEMP:-/tmp}/ultimate-commerce-wporg-upgrade-${run_id}-${attempt}-${slug}"
html="$root/html"
plugins="$html/wp-content/plugins"
memory_ini="$root/uc-cli-memory.ini"
network="uc-upgrade-${run_id}-${attempt}-${WP_VERSION//./-}-${WC_VERSION//./-}"
db="${network}-db"
cli_image="wordpress:cli-php${PHP_VERSION}"
db_image="mariadb:11.4"

cleanup() {
  docker rm -f "$db" >/dev/null 2>&1 || true
  docker network rm "$network" >/dev/null 2>&1 || true
  if [[ -d "$root" ]]; then
    docker run --rm -u 0:0 -v "$root:/cleanup" "$cli_image" sh -c 'rm -rf /cleanup/* /cleanup/.[!.]* /cleanup/..?*' >/dev/null 2>&1 || true
    rm -rf "$root" >/dev/null 2>&1 || true
  fi
}
trap cleanup EXIT

rm -rf "$root"
mkdir -p "$plugins"
chmod -R 0777 "$root"
printf 'memory_limit=512M\n' > "$memory_ini"

docker run --rm \
  -v "$memory_ini:/usr/local/etc/php/conf.d/zz-uc-memory.ini:ro" \
  "$cli_image" \
  php -r 'if (ini_get("memory_limit") !== "512M") { fwrite(STDERR, "CLI memory override failed: " . ini_get("memory_limit") . PHP_EOL); exit(1); } echo "WP-CLI PHP memory_limit=512M" . PHP_EOL;'

docker network create "$network" >/dev/null

docker run -d \
  --name "$db" \
  --network "$network" \
  -e MARIADB_DATABASE=wordpress \
  -e MARIADB_USER=wordpress \
  -e MARIADB_PASSWORD=wordpress \
  -e MARIADB_ROOT_PASSWORD=root \
  "$db_image" \
  --character-set-server=utf8mb4 \
  --collation-server=utf8mb4_unicode_ci >/dev/null

ready=0
for _ in $(seq 1 60); do
  if docker exec "$db" mariadb-admin ping -h 127.0.0.1 -uroot -proot --silent >/dev/null 2>&1; then
    ready=1
    break
  fi
  sleep 1
done
if [[ "$ready" != "1" ]]; then
  docker logs "$db" >&2 || true
  echo "MariaDB did not become ready." >&2
  exit 1
fi

wpcli() {
  docker run --rm \
    --network "$network" \
    -v "$html:/var/www/html" \
    -v "$workspace:/workspace:ro" \
    -v "$root:/upgrade:ro" \
    -e "UC_UPGRADE_STAGE=${UC_UPGRADE_STAGE:-}" \
    -v "$memory_ini:/usr/local/etc/php/conf.d/zz-uc-memory.ini:ro" \
    "$cli_image" \
    wp "$@"
}

wpcli core download --version="$WP_VERSION" --force
wpcli config create \
  --dbname=wordpress \
  --dbuser=wordpress \
  --dbpass=wordpress \
  --dbhost="$db:3306" \
  --skip-check
wpcli core install \
  --url="http://uc-compat.test" \
  --title="Ultimate Commerce Compatibility" \
  --admin_user=admin \
  --admin_password="matrix-admin-password" \
  --admin_email="matrix-admin@example.test" \
  --skip-email

wc_zip="$root/woocommerce.zip"
curl -fL \
  "https://github.com/woocommerce/woocommerce/releases/download/${WC_VERSION}/woocommerce.zip" \
  -o "$wc_zip"
echo "$WC_SHA256  $wc_zip" | sha256sum -c -
unzip -q "$wc_zip" -d "$plugins"


# Install the *published* WordPress.org archive first; do not reconstruct 0.2.0
# from a historical repository checkout or trust an unverified redirect.
: "${UC_WPORG_020_SHA256:?Pinned public Free 0.2.0 ZIP digest is required}"
if [[ ! "$UC_WPORG_020_SHA256" =~ ^[0-9a-f]{64}$ ]]; then
  echo "Public Free 0.2.0 SHA-256 must be 64 lower-case hex characters." >&2
  exit 1
fi
public_zip="$root/bad-otter-ultimate-commerce-woocommerce.0.2.0.zip"
curl --fail --location --silent --show-error --retry 3 --max-time 120 \
  "https://downloads.wordpress.org/plugin/bad-otter-ultimate-commerce-woocommerce.0.2.0.zip" \
  -o "$public_zip"
printf '%s  %s\n' "$UC_WPORG_020_SHA256" "$public_zip" | sha256sum -c -

# Build *this candidate head* into the exact distributable WordPress.org ZIP.
python3 "$workspace/scripts/build-wordpress-org-package.py" \
  --source packages/ultimate-commerce-for-woocommerce \
  --output-dir "$root/candidate"
candidate_name="bad-otter-ultimate-commerce-woocommerce-0.2.2.zip"
candidate_zip="$root/candidate/$candidate_name"
test -s "$candidate_zip"
( cd "$root/candidate" && sha256sum -c "$candidate_name.sha256" )
python3 "$workspace/scripts/check-wordpress-org-package.py" "$candidate_zip"

# Fail closed on mismatched identity, ambiguous ZIP paths or version metadata.
python3 - "$public_zip" "$candidate_zip" <<'PY'
import pathlib
import re
import stat
import sys
import zipfile

slug = "bad-otter-ultimate-commerce-woocommerce"
entry = f"{slug}/ultimate-commerce-for-woocommerce.php"
for filename, version in ((sys.argv[1], "0.2.0"), (sys.argv[2], "0.2.2")):
    with zipfile.ZipFile(filename) as archive:
        entries = archive.infolist()
        paths = [item.filename for item in entries]
        if not paths or len(set(paths)) != len(paths) or entry not in paths:
            raise SystemExit(f"Invalid or duplicated plugin ZIP entries: {filename}")
        for item in entries:
            path = pathlib.PurePosixPath(item.filename)
            mode = item.external_attr >> 16
            if (
                not item.filename.startswith(slug + "/")
                or item.filename.startswith("/")
                or "\\" in item.filename
                or ".." in path.parts
                or stat.S_IFMT(mode) == stat.S_IFLNK
            ):
                raise SystemExit(f"Unsafe WordPress.org ZIP path: {item.filename}")
        bootstrap = archive.read(entry).decode("utf-8")
        match = re.search(r"(?m)^\s*\*\s*Version:\s*(\S+)\s*$", bootstrap)
        if not match or match.group(1) != version:
            raise SystemExit(f"Unexpected {version} ZIP version header")
        if "Update URI:" in bootstrap:
            raise SystemExit("Public Free ZIP unexpectedly declares a custom updater")
print("Published 0.2.0 and candidate 0.2.2 ZIP identity verified.")
PY

unzip -t "$public_zip" >/dev/null
unzip -t "$candidate_zip" >/dev/null

wpcli plugin activate woocommerce
wpcli wc hpos enable --user=admin

# The official archive must install under the actual WordPress.org slug.
wpcli plugin install /upgrade/bad-otter-ultimate-commerce-woocommerce.0.2.0.zip --activate
wpcli plugin is-active bad-otter-ultimate-commerce-woocommerce

eval_stage() {
  UC_UPGRADE_STAGE="$1" wpcli eval-file /workspace/tests/public-wporg-upgrade.php
}

eval_stage seed

# WP-CLI's --force installer exercises in-place replacement of the installed
# plugin's canonical basename, retaining the database and active-plugin state.
wpcli plugin install "/upgrade/candidate/$candidate_name" --force
wpcli plugin is-active bad-otter-ultimate-commerce-woocommerce
eval_stage upgraded

# Disposable rollback of the exact published ZIP, then retry the upgrade.
# This is a data-preservation/recovery proof, not permission for live rollback.
wpcli plugin install /upgrade/bad-otter-ultimate-commerce-woocommerce.0.2.0.zip --force
wpcli plugin is-active bad-otter-ultimate-commerce-woocommerce
eval_stage rolled_back

wpcli plugin install "/upgrade/candidate/$candidate_name" --force
wpcli plugin is-active bad-otter-ultimate-commerce-woocommerce
eval_stage upgraded_again

wpcli plugin status woocommerce
wpcli plugin status bad-otter-ultimate-commerce-woocommerce
wpcli wc hpos status --user=admin
echo "Official WordPress.org 0.2.0 -> exact Free 0.2.2 ZIP upgrade and rollback passed."
