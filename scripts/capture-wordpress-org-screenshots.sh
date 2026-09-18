#!/usr/bin/env bash
set -euo pipefail

workspace="${GITHUB_WORKSPACE:-$(pwd)}"
run_id="${GITHUB_RUN_ID:-local}"
attempt="${GITHUB_RUN_ATTEMPT:-1}"
root="${RUNNER_TEMP:-/tmp}/ultimate-commerce-wporg-assets-${run_id}-${attempt}"
html="$root/html"
plugins="$html/wp-content/plugins"
mu_plugins="$html/wp-content/mu-plugins"
memory_ini="$root/uc-cli-memory.ini"
network="uc-wporg-assets-${run_id}-${attempt}"
db="${network}-db"
web="${network}-web"
cli_image="wordpress:cli-php8.3"
web_image="wordpress:php8.3-apache"
db_image="mariadb:11.4"
browser_image="mcr.microsoft.com/playwright:v1.63.0-noble"
wc_version="11.1.0"
wc_sha256="ab56c02b4e0b0685702408624822d8f598ba8f9a9333bcc3673b5da24e03e4f1"

cleanup() {
  docker rm -f "$web" "$db" >/dev/null 2>&1 || true
  docker network rm "$network" >/dev/null 2>&1 || true
  if [[ -d "$root" ]]; then
    docker run --rm -u 0:0 -v "$root:/cleanup" "$cli_image" sh -c 'rm -rf /cleanup/* /cleanup/.[!.]* /cleanup/..?*' >/dev/null 2>&1 || true
    rm -rf "$root" >/dev/null 2>&1 || true
  fi
}
trap cleanup EXIT

rm -rf "$root"
mkdir -p "$plugins" "$mu_plugins" "$root/browser" "$root/output" "$workspace/wordpress-org-assets"
chmod -R 0777 "$root"
printf 'memory_limit=512M\n' > "$memory_ini"

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
  exit 1
fi

wpcli() {
  docker run --rm \
    --network "$network" \
    -v "$html:/var/www/html" \
    -v "$workspace:/workspace:ro" \
    -v "$memory_ini:/usr/local/etc/php/conf.d/zz-uc-memory.ini:ro" \
    "$cli_image" \
    wp "$@"
}

wpcli core download --version=7.1.1 --force
wpcli config create --dbname=wordpress --dbuser=wordpress --dbpass=wordpress --dbhost="$db:3306" --skip-check
wpcli core install \
  --url="http://uc-web" \
  --title="Ultimate Commerce Demo Store" \
  --admin_user=admin \
  --admin_password="uc-screenshot-password" \
  --admin_email="screenshots@example.test" \
  --skip-email

wc_zip="$root/woocommerce.zip"
curl -fL "https://github.com/woocommerce/woocommerce/releases/download/${wc_version}/woocommerce.zip" -o "$wc_zip"
echo "$wc_sha256  $wc_zip" | sha256sum -c -
unzip -q "$wc_zip" -d "$plugins"

cp -R "$workspace/packages/ultimate-commerce-for-woocommerce" "$plugins/ultimate-commerce-for-woocommerce"
cp "$workspace/tests/fixtures/accessibility-mu-plugin.php" "$mu_plugins/uc-accessibility-fixture.php"

wpcli plugin activate woocommerce ultimate-commerce-for-woocommerce
wpcli eval-file /workspace/tests/setup-accessibility-fixture.php

docker run -d \
  --name "$web" \
  --network "$network" \
  --network-alias uc-web \
  -v "$html:/var/www/html" \
  "$web_image" >/dev/null

for _ in $(seq 1 60); do
  if docker run --rm --network "$network" "$cli_image" php -r 'exit(@file_get_contents("http://uc-web/") === false ? 1 : 0);' >/dev/null 2>&1; then
    break
  fi
  sleep 1
done

cp "$workspace/tools/accessibility/package.json" "$root/browser/package.json"
cp "$workspace/tools/accessibility/package-lock.json" "$root/browser/package-lock.json"
cp "$workspace/tools/wordpress-org-assets/capture.mjs" "$root/browser/capture.mjs"

docker run --rm \
  --network "$network" \
  -e UC_SCREENSHOT_BASE_URL="http://uc-web" \
  -v "$root/browser:/work" \
  -v "$root/output:/output" \
  -w /work \
  "$browser_image" \
  bash -lc 'npm ci --no-audit --no-fund && node capture.mjs'

cp "$root/output"/screenshot-*.png "$workspace/wordpress-org-assets/"
