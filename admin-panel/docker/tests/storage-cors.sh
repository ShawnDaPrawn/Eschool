#!/usr/bin/env bash
# Runs a real Apache with docker/storage-cors.conf and the origins
# docker/storage-cors-origins.php prints for production, and checks which
# requests for an uploaded file get Access-Control-Allow-Origin (defect D2).
#
#   bash docker/tests/storage-cors.sh      (from admin-panel/, after composer install)
#
# Needs apache2 (installed on GitHub's Ubuntu runners). Without it the test is
# skipped locally and fails in CI.
set -euo pipefail
cd "$(dirname "$0")/../.."

APACHE="$(command -v apache2 || echo /usr/sbin/apache2)"
MODULES=/usr/lib/apache2/modules
if [ ! -x "$APACHE" ] || [ ! -f "$MODULES/mod_headers.so" ]; then
    if [ -n "${CI:-}" ]; then echo "apache2 is missing" >&2; exit 1; fi
    echo "SKIP: apache2 is not installed"
    exit 0
fi

WORK="$(mktemp -d)"
PORT="${STORAGE_CORS_TEST_PORT:-18089}"
trap '[ -f "$WORK/httpd.pid" ] && kill "$(cat "$WORK/httpd.pid")" 2>/dev/null; rm -rf "$WORK"' EXIT

# The origins production allows (config/cors.php: APP_URL and the portals),
# without a cached config.
ORIGINS="$(APP_CONFIG_CACHE="$WORK/none.php" APP_ENV=production APP_URL=https://admin.navigateed.co.za \
    CORS_ALLOWED_ORIGINS= php docker/storage-cors-origins.php)"

mkdir -p "$WORK/public/storage/demo"
printf 'PNG' > "$WORK/public/storage/demo/avatar.png"
printf 'PNG' > "$WORK/public/logo.png"
cat > "$WORK/httpd.conf" <<CONF
ServerRoot "$WORK"
ServerName localhost
Listen 127.0.0.1:$PORT
PidFile "$WORK/httpd.pid"
DefaultRuntimeDir "$WORK"
Mutex file:$WORK default
ErrorLog "$WORK/error.log"
LoadModule mpm_event_module $MODULES/mod_mpm_event.so
LoadModule authz_core_module $MODULES/mod_authz_core.so
LoadModule mime_module $MODULES/mod_mime.so
LoadModule setenvif_module $MODULES/mod_setenvif.so
LoadModule headers_module $MODULES/mod_headers.so
TypesConfig /etc/mime.types
DocumentRoot "$WORK/public"
<Directory "$WORK/public">
    Require all granted
</Directory>
Include "$PWD/docker/storage-cors.conf"
CONF

start() { # STORAGE_CORS_ORIGINS value
    [ -f "$WORK/httpd.pid" ] && kill "$(cat "$WORK/httpd.pid")" 2>/dev/null && sleep 1
    rm -f "$WORK/httpd.pid"
    STORAGE_CORS_ORIGINS="$1" "$APACHE" -f "$WORK/httpd.conf" -k start
    for _ in $(seq 1 50); do
        curl -s -o /dev/null "http://127.0.0.1:$PORT/logo.png" && return 0
        sleep 0.1
    done
    cat "$WORK/error.log" >&2
    return 1
}

failures=0
check() { # description, expected Access-Control-Allow-Origin ("" for none), curl arguments...
    local name="$1" expected="$2"
    shift 2
    local headers allow vary
    headers="$(curl -s -o /dev/null -D - "$@" | tr -d '\r')"
    allow="$(printf '%s\n' "$headers" | sed -n 's/^Access-Control-Allow-Origin: //Ip')"
    vary="$(printf '%s\n' "$headers" | sed -n 's/^Vary: //Ip')"
    if [ "$allow" != "$expected" ]; then
        echo "FAIL $name: Access-Control-Allow-Origin '$allow', expected '$expected'"
        failures=$((failures + 1))
    elif [[ "$*" == *"/storage/"* ]] && [[ "$vary" != *Origin* ]]; then
        echo "FAIL $name: no Vary: Origin"
        failures=$((failures + 1))
    else
        echo "ok   $name"
    fi
}

FILE="http://127.0.0.1:$PORT/storage/demo/avatar.png"
start "$ORIGINS"
check "the learner portal" https://app.navigateed.co.za -H 'Origin: https://app.navigateed.co.za' "$FILE"
check "the teacher portal" https://teach.navigateed.co.za -H 'Origin: https://teach.navigateed.co.za' "$FILE"
check "the panel itself" https://admin.navigateed.co.za -H 'Origin: https://admin.navigateed.co.za' "$FILE"
check "HEAD from a portal" https://app.navigateed.co.za -I -H 'Origin: https://app.navigateed.co.za' "$FILE"
check "an unknown origin" "" -H 'Origin: https://evil.example' "$FILE"
check "a look-alike origin" "" -H 'Origin: https://app.navigateed.co.za.evil.example' "$FILE"
check "http instead of https" "" -H 'Origin: http://app.navigateed.co.za' "$FILE"
check "the null origin" "" -H 'Origin: null' "$FILE"
check "no Origin (the phone apps)" "" "$FILE"
check "POST from a portal" "" -X POST -H 'Origin: https://app.navigateed.co.za' "$FILE"
check "OPTIONS from a portal" "" -X OPTIONS -H 'Origin: https://app.navigateed.co.za' "$FILE"
check "a file outside /storage" "" -H 'Origin: https://app.navigateed.co.za' "http://127.0.0.1:$PORT/logo.png"

# The Dockerfile's default, before the entrypoint sets the origins.
start '(?!)'
check "the image default allows nobody" "" -H 'Origin: https://app.navigateed.co.za' "$FILE"

[ "$failures" -eq 0 ] || { echo "$failures check(s) failed"; exit 1; }
echo "storage CORS: all checks passed"
