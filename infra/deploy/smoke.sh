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

# A real login page must issue a browser session that JavaScript cannot read.
# Never print cookie values into deployment logs.
session_cookie=0
http_only_pattern=';[[:space:]]*httponly([[:space:]]*;|[[:space:]]*$)'
secure_cookie_pattern=';[[:space:]]*secure([[:space:]]*;|[[:space:]]*$)'
same_site_pattern=';[[:space:]]*samesite=(lax|strict|none)([[:space:]]*;|[[:space:]]*$)'
while IFS= read -r header; do
    lower="${header,,}"
    if [[ "$lower" == set-cookie:* && "$lower" =~ $http_only_pattern ]]; then
        session_cookie=1
        if [[ "$PANEL" == https://* && ! "$lower" =~ $secure_cookie_pattern ]]; then
            echo "FAIL  a login session cookie is missing Secure on HTTPS"
            failures=$((failures + 1))
        fi
        if [[ ! "$lower" =~ $same_site_pattern ]]; then
            echo "FAIL  a login session cookie is missing SameSite"
            failures=$((failures + 1))
        fi
    fi
done < "$headers"
if [ "$session_cookie" -eq 0 ]; then
    echo "FAIL  the login page did not issue an HttpOnly session cookie"
    failures=$((failures + 1))
else
    echo "ok    login session cookie inspected"
fi

# This request has no cookie or bearer token: private account context must
# refuse it with the documented v2 authentication error.
status="$(curl -sS -o "$body" -w '%{http_code}' --max-time 30 \
    --retry 4 --retry-delay 5 --retry-connrefused "$PANEL/api/v2/me/context" || echo 000)"
if [ "$status" = 401 ] && grep -qF '"code":"unauthenticated"' "$body"; then
    echo "ok    anonymous access to private account context is refused"
else
    echo "FAIL  private account context did not return the expected authentication error ($status)"
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
