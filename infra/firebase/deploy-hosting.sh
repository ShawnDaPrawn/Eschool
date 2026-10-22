#!/usr/bin/env bash
# Deploys the Firebase Hosting sites defined in firebase.json (the documentation
# site and, once the web builds exist, the learner and parent portal and the
# teacher portal) to one Firebase project.
#
# Each hosting target in firebase.json is pointed at the site named by the
# environment variable FIREBASE_SITE_<TARGET> (target in upper case, dashes as
# underscores: target "app" reads FIREBASE_SITE_APP), so the same firebase.json
# serves staging and production. A firebase.json without targets deploys to
# the project's default site.
#
# Usage: infra/firebase/deploy-hosting.sh FIREBASE_PROJECT_ID [MESSAGE]
# Needs the Firebase CLI (firebase-tools) and jq, signed in through
# GOOGLE_APPLICATION_CREDENTIALS (the deploy workflows) or `firebase login`.
# Build the Flutter web apps first; the documentation builds in firebase.json's
# predeploy step.

set -euo pipefail

PROJECT="${1:?usage: deploy-hosting.sh FIREBASE_PROJECT_ID [MESSAGE]}"
MESSAGE="${2:-}"
cd "$(dirname "${BASH_SOURCE[0]}")/../.."

mapfile -t targets < <(jq -r '.hosting | if type == "array" then .[] else . end | .target // empty' firebase.json)

for target in "${targets[@]}"; do
    var="FIREBASE_SITE_$(printf '%s' "$target" | tr '[:lower:]-' '[:upper:]_')"
    site="${!var:-}"
    if [ -z "$site" ]; then
        echo "error: set $var to the Firebase Hosting site for target \"$target\"" >&2
        exit 1
    fi
    # Clear first: a target can list several sites, and a committed .firebaserc
    # must not add another environment's site to this deploy.
    firebase target:clear hosting "$target" --project "$PROJECT" >/dev/null 2>&1 || true
    firebase target:apply hosting "$target" "$site" --project "$PROJECT"
done

args=(deploy --only hosting --project "$PROJECT" --non-interactive)
[ -z "$MESSAGE" ] || args+=(--message "$MESSAGE")
firebase "${args[@]}"
