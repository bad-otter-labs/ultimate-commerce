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
root="${RUNNER_TEMP:-/tmp}/ultimate-commerce-compat-${run_id}-${attempt}-${slug}"
html="$root/html"
plugins="$html/wp-content/plugins"
memory_ini="$root/uc-cli-memory.ini"
network="uc-compat-${run_id}-${attempt}-${WP_VERSION//./-}-${WC_VERSION//./-}"
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

cp -R "$workspace/packages/ultimate-commerce-for-woocommerce" "$plugins/ultimate-commerce-for-woocommerce"

wpcli plugin activate woocommerce ultimate-commerce-for-woocommerce
wpcli wc hpos enable --user=admin

docker run --rm \
  --network "$network" \
  -e UC_EXPECT_WORDPRESS="$WP_VERSION" \
  -e UC_EXPECT_WOOCOMMERCE="$WC_VERSION" \
  -e UC_EXPECT_PHP="$PHP_VERSION" \
  -v "$html:/var/www/html" \
  -v "$workspace:/workspace:ro" \
  -v "$memory_ini:/usr/local/etc/php/conf.d/zz-uc-memory.ini:ro" \
  "$cli_image" \
  wp eval-file /workspace/tests/live-compatibility-smoke.php

wpcli plugin status woocommerce
wpcli plugin status ultimate-commerce-for-woocommerce
wpcli wc hpos status --user=admin

echo "Compatibility case passed: WordPress $WP_VERSION / WooCommerce $WC_VERSION / PHP $PHP_VERSION"
