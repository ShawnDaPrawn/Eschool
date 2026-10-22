#!/usr/bin/env bash
# Smoke checks after a deploy: the panel, the API, the worker and (optionally)
# the web portals answer, and the new build is the one serving.
#
# Usage: infra/deploy/smoke.sh PANEL_URL [PORTAL_URL ...]
#   PANEL_URL   e.g. https://staging-admin.navigateed.co.za
#   PORTAL_URL  each web portal to check, e.g. https://staging-app.navigateed.co.za
#
# Needs only curl. Exits non-zero when any check fails. Used by
# .github/workflows/deploy-environment.yml and engineering/runbooks/deploy.md.

set -euo pipefail

PANEL="${1:?usage: smoke.sh PANEL_URL [PORTAL_URL ...]}"
PANEL="${PANEL%/}"
shift

failures=0
body="$(mktemp)"
headers="$(mktemp)"
trap 'rm -f "$body" "$headers"' EXIT

# check NAME URL [TEXT]: expects HTTP 200 and, when given, TEXT in the body.
check() {
    local name="$1" url="$2" text="${3:-}" status
    status="$(curl -sS -o "$body" -D "$headers" -w '%{http_code}' --max-time 30 \
        --retry 4 --retry-delay 5 --retry-connrefused "$url" || echo 000)"
    if [ "$status" != 200 ]; then
        echo "FAIL  $name: $url answered $status"
        failures=$((failures + 1))
    elif [ -n "$text" ] && ! grep -qF -- "$text" "$body"; then
        echo "FAIL  $name: $url answered 200 without \"$text\""
        failures=$((failures + 1))
    else
        echo "ok    $name ($url)"
    fi
}

check "health" "$PANEL/up"
check "panel sign-in page" "$PANEL/login" '<form'
# The request id header comes from this release's middleware; an old revision
# still serving would not send it.
if grep -qi '^x-request-id:' "$headers"; then
    echo "ok    request id header on /login"
else
    echo "FAIL  no X-Request-Id header on /login: is the new revision serving?"
    failures=$((failures + 1))
fi
check "worker keeps up with the queue" "$PANEL/up/worker" '"status"'
check "public API" "$PANEL/api/settings?type=app_settings" '"error":false'

for portal in "$@"; do
    [ -n "$portal" ] || continue
    check "web portal" "${portal%/}/" '<html'
done

if [ "$failures" -gt 0 ]; then
    echo "$failures smoke check(s) failed"
    exit 1
fi
echo "All smoke checks passed"
