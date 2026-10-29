#!/usr/bin/env bash
# shellcheck source-path=SCRIPTDIR
# Creates, or brings up to date, one NavigateEd environment on Google Cloud in
# africa-south1: APIs, Artifact Registry, service accounts with least privilege,
# Secret Manager secrets (names only), Cloud SQL MySQL 8 with backups, the
# uploads bucket, the Cloud Run services navigateed-admin and navigateed-worker,
# the Cloud Run Job navigateed-scheduler with its every-minute Cloud Scheduler
# trigger, Workload Identity Federation for GitHub Actions, and monitoring
# (uptime checks, log-based alerts, Error Reporting).
#
# Safe to run again: every step checks what exists and only creates what is
# missing or updates settings in place. It never deletes anything, never changes
# a Cloud Run service's image (the deploy workflows do that), and in production
# never writes a secret value or a database password.
#
# Usage (from a machine or Cloud Shell signed in as a project owner):
#   ALERT_EMAIL=alerts@example.org infra/gcp/provision.sh staging
#   ALERT_EMAIL=alerts@example.org CONFIRM_PRODUCTION=yes infra/gcp/provision.sh production
#   infra/gcp/provision.sh staging --dry-run     # print the changes, make none
#
# Options (environment variables):
#   ALERT_EMAIL         required: where alerts are e-mailed
#   CONFIRM_PRODUCTION  must be "yes" to change production
#   ALLOW_SQL_RESTART   "yes" to apply a Cloud SQL change that restarts the
#                       instance (tier, high availability, enabling binary logs)
#
# Needs gcloud, jq, curl and openssl. Settings per environment are in
# infra/gcp/env/<environment>.env. Runbook: engineering/runbooks/environments.md.
#
# Status: written and checked with --dry-run and shellcheck; not yet run against
# a real project.

set -euo pipefail

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

usage() {
    sed -n '3,/^# Status/p' "${BASH_SOURCE[0]}" | sed 's/^# \{0,1\}//'
}

die() { printf 'error: %s\n' "$*" >&2; exit 1; }
log() { printf '\n==> %s\n' "$*" >&2; }
note() { printf '    %s\n' "$*" >&2; }

ENV_NAME="${1:-}"
[ $# -gt 0 ] && shift
DRY_RUN=0
for arg in "$@"; do
    case "$arg" in
        --dry-run) DRY_RUN=1 ;;
        -h|--help) usage; exit 0 ;;
        *) usage >&2; die "unknown option: $arg" ;;
    esac
done
case "$ENV_NAME" in
    staging|production) ;;
    -h|--help) usage; exit 0 ;;
    *) usage >&2; die "first argument must be staging or production" ;;
esac

# shellcheck source=env/staging.env
source "$HERE/env/$ENV_NAME.env"

ALERT_EMAIL="${ALERT_EMAIL:-}"
[ -n "$ALERT_EMAIL" ] || die "set ALERT_EMAIL to the address that receives alerts"
if [ "$ENVIRONMENT" = production ] && [ "$DRY_RUN" = 0 ] && [ "${CONFIRM_PRODUCTION:-}" != yes ]; then
    die "this changes production: read engineering/runbooks/environments.md, then re-run with CONFIRM_PRODUCTION=yes"
fi
for tool in gcloud jq curl openssl; do
    command -v "$tool" >/dev/null 2>&1 || die "$tool is required"
done

G=(gcloud --project="$PROJECT_ID" --quiet)

# Runs a command that changes something; with --dry-run, prints it instead.
run() {
    if [ "$DRY_RUN" = 1 ]; then
        printf '    + %s\n' "$(printf '%q ' "$@")" >&2
    else
        "$@"
    fi
}

# True when a read-only command succeeds (used to test whether something exists).
exists() { "$@" >/dev/null 2>&1; }

sa_email() { printf '%s@%s.iam.gserviceaccount.com' "$1" "$PROJECT_ID"; }

SA_ADMIN_EMAIL="$(sa_email "$SA_ADMIN")"
SA_WORKER_EMAIL="$(sa_email "$SA_WORKER")"
SA_INVOKER_EMAIL="$(sa_email "$SA_SCHEDULER_INVOKER")"
SA_DEPLOYER_EMAIL="$(sa_email "$SA_DEPLOYER")"
CONNECTION_NAME="$PROJECT_ID:$REGION:$SQL_INSTANCE"
STORAGE_MOUNT=/var/www/html/storage/app
PLACEHOLDER_SERVICE_IMAGE=us-docker.pkg.dev/cloudrun/container/hello
PLACEHOLDER_JOB_IMAGE=us-docker.pkg.dev/cloudrun/container/job
WIF_POOL=github
WIF_PROVIDER=github-actions
BACKUP_ROLE=navigateedReleaseBackup

SECRET_APP_KEY=navigateed-app-key
SECRET_DB_PASSWORD=navigateed-db-password
SECRET_ADMIN_PASSWORD=navigateed-admin-password
# Created empty; the product owner adds each value (environments.md,
# "Secrets"). Transactional mail (ADR 0004), then the keys that used to sit in
# the panel's settings rows (work stream S6, wiki Configuration).
SECRET_SES_KEY_ID=navigateed-ses-access-key-id
SECRET_SES_SECRET=navigateed-ses-secret-access-key
SECRET_MAIL_PASSWORD=navigateed-mail-password
SECRET_STRIPE_KEY=navigateed-stripe-secret-key
SECRET_STRIPE_WEBHOOK=navigateed-stripe-webhook-secret
SECRET_RECAPTCHA=navigateed-recaptcha-secret-key
SECRET_FCM=navigateed-fcm-service-account
SECRET_DIARY_KEY=navigateed-diary-key

# Those secrets as "variable|secret|roles that mount it". Each is mounted once
# it has a value: Cloud Run refuses a revision that mounts a secret without a
# version. The worker sends the queued mail and pushes; the scheduler job is
# also the one-off artisan runner (runbooks/incident.md), so maintenance
# commands such as secrets:audit-settings see what the panel sees.
OPTIONAL_SECRETS=(
    "SES_ACCESS_KEY_ID|$SECRET_SES_KEY_ID|web worker scheduler"
    "SES_SECRET_ACCESS_KEY|$SECRET_SES_SECRET|web worker scheduler"
    "MAIL_PASSWORD|$SECRET_MAIL_PASSWORD|web worker scheduler"
    "FCM_SERVICE_ACCOUNT_JSON|$SECRET_FCM|web worker scheduler"
    "STRIPE_SECRET_KEY|$SECRET_STRIPE_KEY|web scheduler"
    "STRIPE_WEBHOOK_SECRET|$SECRET_STRIPE_WEBHOOK|web scheduler"
    "RECAPTCHA_SECRET_KEY|$SECRET_RECAPTCHA|web scheduler"
    "DIARY_KEY|$SECRET_DIARY_KEY|web scheduler"
)

PROJECT_NUMBER="$("${G[@]}" projects describe "$PROJECT_ID" --format='value(projectNumber)' 2>/dev/null || true)"
if [ -z "$PROJECT_NUMBER" ]; then
    [ "$DRY_RUN" = 1 ] || die "cannot read project $PROJECT_ID: create it, link billing and sign in (gcloud auth login)"
    PROJECT_NUMBER=PROJECT_NUMBER
fi
# Cloud Run's deterministic address for a service.
RUN_URL="https://$SERVICE_ADMIN-$PROJECT_NUMBER.$REGION.run.app"
PANEL_URL="${APP_URL:-$RUN_URL}"
PANEL_HOST="${PANEL_URL#https://}"
PANEL_HOST="${PANEL_HOST%%/*}"

log "NavigateEd $ENVIRONMENT: project $PROJECT_ID ($PROJECT_NUMBER), region $REGION$([ "$DRY_RUN" = 1 ] && echo ', dry run')"

enable_apis() {
    log "Enabling APIs"
    run "${G[@]}" services enable \
        run.googleapis.com sqladmin.googleapis.com secretmanager.googleapis.com \
        artifactregistry.googleapis.com cloudscheduler.googleapis.com iam.googleapis.com \
        iamcredentials.googleapis.com sts.googleapis.com monitoring.googleapis.com \
        logging.googleapis.com clouderrorreporting.googleapis.com storage.googleapis.com \
        cloudresourcemanager.googleapis.com serviceusage.googleapis.com firebasehosting.googleapis.com
}

ensure_artifact_registry() {
    log "Artifact Registry repository $ARTIFACT_REPOSITORY"
    if ! exists "${G[@]}" artifacts repositories describe "$ARTIFACT_REPOSITORY" --location="$REGION"; then
        run "${G[@]}" artifacts repositories create "$ARTIFACT_REPOSITORY" \
            --repository-format=docker --location="$REGION" \
            --description="NavigateEd container images ($ENVIRONMENT)"
    fi
    run "${G[@]}" artifacts repositories set-cleanup-policies "$ARTIFACT_REPOSITORY" \
        --location="$REGION" --policy="$HERE/artifact-cleanup-policy.json" --no-dry-run
}

ensure_service_account() { # name, display name
    if ! exists "${G[@]}" iam service-accounts describe "$(sa_email "$1")"; then
        run "${G[@]}" iam service-accounts create "$1" --display-name="$2"
    fi
}

ensure_service_accounts() {
    log "Service accounts"
    ensure_service_account "$SA_ADMIN" "NavigateEd panel (Cloud Run $SERVICE_ADMIN)"
    ensure_service_account "$SA_WORKER" "NavigateEd worker and scheduler (Cloud Run)"
    ensure_service_account "$SA_SCHEDULER_INVOKER" "Cloud Scheduler: starts $JOB_SCHEDULER"
    ensure_service_account "$SA_DEPLOYER" "GitHub Actions deployer ($ENVIRONMENT)"

    # Backups before a release need only these, not Cloud SQL Editor.
    local permissions=cloudsql.backupRuns.create,cloudsql.backupRuns.get,cloudsql.backupRuns.list,cloudsql.instances.get
    if exists "${G[@]}" iam roles describe "$BACKUP_ROLE"; then
        run "${G[@]}" iam roles update "$BACKUP_ROLE" --permissions="$permissions"
    else
        run "${G[@]}" iam roles create "$BACKUP_ROLE" --title="NavigateEd release backup" \
            --description="Create and read Cloud SQL backups before a release" \
            --permissions="$permissions" --stage=GA
    fi
}

bind_project() { # member, role
    run "${G[@]}" projects add-iam-policy-binding "$PROJECT_ID" --member="$1" --role="$2" --condition=None --format=none
}

ensure_secret() {
    if ! exists "${G[@]}" secrets describe "$1"; then
        run "${G[@]}" secrets create "$1" --replication-policy=user-managed --locations="$REGION" --labels=app=navigateed
    fi
}

secret_has_version() {
    [ -n "$("${G[@]}" secrets versions list "$1" --filter='state=ENABLED' --limit=1 --format='value(name)' 2>/dev/null || true)" ]
}

# Staging only: gives a secret its first value, generated here and piped
# straight into Secret Manager (never printed). Production values are added by
# the product owner.
seed_secret() { # name, generator command...
    local name="$1"
    shift
    if [ "$ENVIRONMENT" = production ]; then
        secret_has_version "$name" || note "production: $name has no value; add one (environments.md, 'Secrets')"
        return
    fi
    if secret_has_version "$name"; then
        return
    fi
    if [ "$DRY_RUN" = 1 ]; then
        note "+ (generate) | gcloud secrets versions add $name --data-file=-"
    else
        "$@" | "${G[@]}" secrets versions add "$name" --data-file=- --format=none
    fi
}

grant_secret() { # secret, service account e-mail
    run "${G[@]}" secrets add-iam-policy-binding "$1" --member="serviceAccount:$2" \
        --role=roles/secretmanager.secretAccessor --format=none
}

gen_app_key() { printf 'base64:%s' "$(openssl rand -base64 32)"; }
gen_password() { openssl rand -base64 30 | tr -d '/+=\n' | cut -c1-32; }

ensure_secrets() {
    log "Secret Manager (names only)"
    local name
    for name in "$SECRET_APP_KEY" "$SECRET_DB_PASSWORD" "$SECRET_ADMIN_PASSWORD"; do
        ensure_secret "$name"
    done
    seed_secret "$SECRET_APP_KEY" gen_app_key
    seed_secret "$SECRET_ADMIN_PASSWORD" gen_password
    # The database password is seeded with the database user (ensure_sql).

    for name in "$SECRET_APP_KEY" "$SECRET_DB_PASSWORD" "$SECRET_ADMIN_PASSWORD"; do
        grant_secret "$name" "$SA_ADMIN_EMAIL"
    done
    for name in "$SECRET_APP_KEY" "$SECRET_DB_PASSWORD"; do
        grant_secret "$name" "$SA_WORKER_EMAIL"
    done

    # The worker's account runs the worker and the scheduler job.
    local entry var secret roles
    for entry in "${OPTIONAL_SECRETS[@]}"; do
        IFS='|' read -r var secret roles <<< "$entry"
        ensure_secret "$secret"
        case " $roles " in *" web "*) grant_secret "$secret" "$SA_ADMIN_EMAIL" ;; esac
        case " $roles " in *" worker "* | *" scheduler "*) grant_secret "$secret" "$SA_WORKER_EMAIL" ;; esac
    done
}

ensure_sql() {
    log "Cloud SQL $SQL_INSTANCE ($SQL_TIER, $SQL_AVAILABILITY)"
    local pitr_args=()
    if [ "$SQL_POINT_IN_TIME_RECOVERY" = true ]; then
        pitr_args=(--enable-bin-log --retained-transaction-log-days="$SQL_TRANSACTION_LOG_DAYS")
    fi

    if ! exists "${G[@]}" sql instances describe "$SQL_INSTANCE"; then
        run "${G[@]}" sql instances create "$SQL_INSTANCE" \
            --database-version=MYSQL_8_0 --edition=enterprise --region="$REGION" \
            --tier="$SQL_TIER" --availability-type="$SQL_AVAILABILITY" \
            --storage-type=SSD --storage-size="${SQL_STORAGE_GB}GB" --storage-auto-increase \
            --backup-start-time="$SQL_BACKUP_START" --retained-backups-count="$SQL_RETAINED_BACKUPS" \
            "${pitr_args[@]}" \
            --maintenance-window-day=SUN --maintenance-window-hour=0 \
            --database-flags=character_set_server=utf8mb4 \
            --deletion-protection
    else
        local current tier availability binlog
        current="$("${G[@]}" sql instances describe "$SQL_INSTANCE" \
            --format='value(settings.tier,settings.availabilityType,settings.backupConfiguration.binaryLogEnabled)')"
        read -r tier availability binlog <<<"$current"
        local patch=(--backup-start-time="$SQL_BACKUP_START" --retained-backups-count="$SQL_RETAINED_BACKUPS" --deletion-protection)
        local restart=()
        [ "$tier" = "$SQL_TIER" ] || restart+=(--tier="$SQL_TIER")
        [ "$availability" = "$SQL_AVAILABILITY" ] || restart+=(--availability-type="$SQL_AVAILABILITY")
        if [ "$SQL_POINT_IN_TIME_RECOVERY" = true ]; then
            if [ "${binlog,,}" = true ]; then
                patch+=(--retained-transaction-log-days="$SQL_TRANSACTION_LOG_DAYS")
            else
                restart+=("${pitr_args[@]}")
            fi
        fi
        if [ ${#restart[@]} -gt 0 ]; then
            if [ "${ALLOW_SQL_RESTART:-}" = yes ]; then
                note "applying ${restart[*]} (the instance restarts for a few minutes)"
                patch+=("${restart[@]}")
            else
                note "NOT applied, needs a restart: ${restart[*]} (now: tier=$tier availability=$availability binlog=$binlog)"
                note "re-run with ALLOW_SQL_RESTART=yes in a quiet hour to apply"
            fi
        fi
        run "${G[@]}" sql instances patch "$SQL_INSTANCE" "${patch[@]}"
    fi

    if ! exists "${G[@]}" sql databases describe "$DB_NAME" --instance="$SQL_INSTANCE"; then
        run "${G[@]}" sql databases create "$DB_NAME" --instance="$SQL_INSTANCE" \
            --charset=utf8mb4 --collation=utf8mb4_unicode_ci
    fi

    if "${G[@]}" sql users list --instance="$SQL_INSTANCE" --format='value(name)' 2>/dev/null | grep -qx "$DB_USER"; then
        return
    fi
    if [ "$ENVIRONMENT" = production ]; then
        note "production: database user $DB_USER does not exist; check DB_USER in env/production.env against the live service"
        return
    fi
    if [ "$DRY_RUN" = 1 ]; then
        note "+ create database user $DB_USER with a generated password stored in $SECRET_DB_PASSWORD"
        return
    fi
    local password
    if secret_has_version "$SECRET_DB_PASSWORD"; then
        password="$("${G[@]}" secrets versions access latest --secret="$SECRET_DB_PASSWORD")"
    else
        password="$(gen_password)"
        printf '%s' "$password" | "${G[@]}" secrets versions add "$SECRET_DB_PASSWORD" --data-file=- --format=none
    fi
    "${G[@]}" sql users create "$DB_USER" --instance="$SQL_INSTANCE" --host=% --password="$password" --format=none
}

ensure_bucket() {
    log "Cloud Storage bucket gs://$BUCKET"
    if ! exists "${G[@]}" storage buckets describe "gs://$BUCKET"; then
        run "${G[@]}" storage buckets create "gs://$BUCKET" --location="$REGION" \
            --uniform-bucket-level-access --public-access-prevention
    fi
    if [ "$BUCKET_VERSIONING" = true ]; then
        run "${G[@]}" storage buckets update "gs://$BUCKET" --versioning \
            --lifecycle-file="$HERE/bucket-lifecycle.json"
    fi
    local sa
    for sa in "$SA_ADMIN_EMAIL" "$SA_WORKER_EMAIL"; do
        run "${G[@]}" storage buckets add-iam-policy-binding "gs://$BUCKET" \
            --member="serviceAccount:$sa" --role=roles/storage.objectUser --format=none
    done
}

grant_runtime_roles() {
    log "Runtime permissions"
    bind_project "serviceAccount:$SA_ADMIN_EMAIL" roles/cloudsql.client
    bind_project "serviceAccount:$SA_WORKER_EMAIL" roles/cloudsql.client
}

# Environment shared by the panel, the worker and the scheduler. '|' separates
# entries because some values contain commas (gcloud topic escaping).
app_env() { # role
    local pairs=(
        "CONTAINER_ROLE=$1"
        APP_ENV=production APP_DEBUG=false
        LOG_CHANNEL=json "LOG_LEVEL=$LOG_LEVEL" "GOOGLE_CLOUD_PROJECT=$PROJECT_ID"
        DB_CONNECTION=mysql "DB_SOCKET=/cloudsql/$CONNECTION_NAME" DB_HOST=localhost DB_PORT=3306
        "DB_DATABASE=$DB_NAME" "DB_USERNAME=$DB_USER"
        CACHE_DRIVER=database SESSION_DRIVER=database QUEUE_CONNECTION=database
        TIMEZONE=Africa/Johannesburg
        "MAIL_MAILER=${MAIL_MAILER:-ses}" "AWS_SES_REGION=${AWS_SES_REGION:-af-south-1}"
        "SESSION_SAME_SITE=${SESSION_SAME_SITE:-lax}"
        "LEARNER_INVITATION_LINK_DAYS=${LEARNER_INVITATION_LINK_DAYS:-7}"
        "LEARNER_SETUP_CODE_DAYS=${LEARNER_SETUP_CODE_DAYS:-30}"
    )
    # Production keeps its live APP_URL until env/production.env names one.
    if [ -n "$APP_URL" ] || [ "$ENVIRONMENT" != production ]; then
        pairs+=("APP_URL=$PANEL_URL")
    fi
    # With a custom domain, the run.app address must stay accepted.
    [ "$PANEL_URL" = "$RUN_URL" ] || pairs+=("TRUSTED_HOSTS=${RUN_URL#https://}")
    # The web portals sign in with the session cookie (cookie mode) only when
    # the panel, which serves the API, is under the parent domain they share
    # (SESSION_DOMAIN, .navigateed.co.za): the session and XSRF-TOKEN cookies
    # are set there so app. and teach. send and read them. On any other address
    # (the run.app one) only the panel itself is stateful, SESSION_DOMAIN is
    # cleared, and the deploy workflow builds the portals with bearer tokens.
    local panel_host="${PANEL_URL#https://}"
    panel_host="${panel_host%%/*}"
    if [ -n "${SESSION_DOMAIN:-}" ] && [[ ".$panel_host" == *"$SESSION_DOMAIN" ]]; then
        pairs+=("SESSION_DOMAIN=$SESSION_DOMAIN")
        [ -z "$SANCTUM_STATEFUL_DOMAINS" ] || pairs+=("SANCTUM_STATEFUL_DOMAINS=$SANCTUM_STATEFUL_DOMAINS")
    else
        pairs+=("SESSION_DOMAIN=null" "SANCTUM_STATEFUL_DOMAINS=$panel_host")
    fi
    [ -z "$SESSION_COOKIE" ] || pairs+=("SESSION_COOKIE=$SESSION_COOKIE")
    [ -z "${AWS_SES_CONFIGURATION_SET:-}" ] || pairs+=("AWS_SES_CONFIGURATION_SET=$AWS_SES_CONFIGURATION_SET")
    [ -z "${SES_SNS_TOPIC_ARNS:-}" ] || pairs+=("SES_SNS_TOPIC_ARNS=$SES_SNS_TOPIC_ARNS")
    # Empty: config/cors.php allows the Q0.2 portal hostnames for this APP_URL.
    [ -z "${CORS_ALLOWED_ORIGINS:-}" ] || pairs+=("CORS_ALLOWED_ORIGINS=$CORS_ALLOWED_ORIGINS")
    if [ "$1" = web ] && [ -n "$ADMIN_EMAIL" ]; then
        pairs+=("ADMIN_EMAIL=$ADMIN_EMAIL")
    fi
    local IFS='|'
    printf '^|^%s' "${pairs[*]}"
}

volume_args() { # describe command...: prints nothing when the uploads mount exists
    local mounts
    mounts="$("$@" --format='value(spec.template.spec.containers[0].volumeMounts[].mountPath,spec.template.spec.template.spec.containers[0].volumeMounts[].mountPath)' 2>/dev/null || true)"
    case "$mounts" in
        *"$STORAGE_MOUNT"*) ;;
        *) printf '%s\n' "--add-volume=name=storage,type=cloud-storage,bucket=$BUCKET" "--add-volume-mount=volume=storage,mount-path=$STORAGE_MOUNT" ;;
    esac
}

ensure_service() { # name, role, extra flags...
    local name="$1" role="$2"
    shift 2
    local describe=("${G[@]}" run services describe "$name" --region="$REGION")
    local volumes=()
    mapfile -t volumes < <(volume_args "${describe[@]}")
    local common=(
        --region="$REGION" --execution-environment=gen2
        --set-cloudsql-instances="$CONNECTION_NAME"
        --update-env-vars="$(app_env "$role")"
        --update-labels="app=navigateed,environment=$ENVIRONMENT,role=$role"
        "$@" "${volumes[@]}"
    )
    if exists "${describe[@]}"; then
        run "${G[@]}" run services update "$name" "${common[@]}"
    else
        # Created with Google's sample image; the first deploy puts ours in.
        run "${G[@]}" run deploy "$name" --image="$PLACEHOLDER_SERVICE_IMAGE" --no-allow-unauthenticated "${common[@]}"
    fi
}

# The optional secrets a role mounts, as extra --update-secrets entries
# (",VAR=secret:latest"), leaving out those without a value yet.
optional_secrets() { # role
    local entry var secret roles
    for entry in "${OPTIONAL_SECRETS[@]}"; do
        IFS='|' read -r var secret roles <<< "$entry"
        case " $roles " in *" $1 "*) ;; *) continue ;; esac
        if secret_has_version "$secret"; then
            printf ',%s=%s:latest' "$var" "$secret"
        else
            note "$1: $var not mounted, $secret has no value yet (environments.md, 'Secrets'); re-run after adding it"
        fi
    done
}

ensure_run_services() {
    log "Cloud Run service $SERVICE_ADMIN (public)"
    ensure_service "$SERVICE_ADMIN" web \
        --service-account="$SA_ADMIN_EMAIL" \
        --update-secrets="APP_KEY=$SECRET_APP_KEY:latest,DB_PASSWORD=$SECRET_DB_PASSWORD:latest,ADMIN_PASSWORD=$SECRET_ADMIN_PASSWORD:latest$(optional_secrets web)" \
        --cpu="$ADMIN_CPU" --memory="$ADMIN_MEMORY" --concurrency="$ADMIN_CONCURRENCY" --timeout=300 \
        --min-instances="$ADMIN_MIN_INSTANCES" --max-instances="$ADMIN_MAX_INSTANCES" \
        --ingress=all --cpu-boost
    run "${G[@]}" run services add-iam-policy-binding "$SERVICE_ADMIN" --region="$REGION" \
        --member=allUsers --role=roles/run.invoker --format=none

    log "Cloud Run service $SERVICE_WORKER (no public ingress, CPU always allocated)"
    ensure_service "$SERVICE_WORKER" worker \
        --service-account="$SA_WORKER_EMAIL" \
        --update-secrets="APP_KEY=$SECRET_APP_KEY:latest,DB_PASSWORD=$SECRET_DB_PASSWORD:latest$(optional_secrets worker)" \
        --cpu="$WORKER_CPU" --memory="$WORKER_MEMORY" --timeout=300 \
        --min-instances="$WORKER_MIN_INSTANCES" --max-instances="$WORKER_MAX_INSTANCES" \
        --ingress=internal --no-cpu-throttling
}

ensure_scheduler() {
    log "Cloud Run Job $JOB_SCHEDULER and its every-minute trigger"
    local describe=("${G[@]}" run jobs describe "$JOB_SCHEDULER" --region="$REGION")
    local volumes=()
    mapfile -t volumes < <(volume_args "${describe[@]}")
    local env secrets common
    env="$(app_env scheduler)"
    secrets="APP_KEY=$SECRET_APP_KEY:latest,DB_PASSWORD=$SECRET_DB_PASSWORD:latest$(optional_secrets scheduler)"
    common=(
        --region="$REGION" --service-account="$SA_WORKER_EMAIL"
        --tasks=1 --max-retries=0 --task-timeout="$JOB_TASK_TIMEOUT"
        --cpu="$JOB_CPU" --memory="$JOB_MEMORY"
        --set-cloudsql-instances="$CONNECTION_NAME"
    )
    if exists "${describe[@]}"; then
        run "${G[@]}" run jobs update "$JOB_SCHEDULER" "${common[@]}" \
            --update-env-vars="$env" --update-secrets="$secrets" \
            --update-labels="app=navigateed,environment=$ENVIRONMENT,role=scheduler" "${volumes[@]}"
    else
        run "${G[@]}" run jobs create "$JOB_SCHEDULER" --image="$PLACEHOLDER_JOB_IMAGE" "${common[@]}" \
            --set-env-vars="$env" --set-secrets="$secrets" \
            --labels="app=navigateed,environment=$ENVIRONMENT,role=scheduler" "${volumes[@]}"
    fi
    run "${G[@]}" run jobs add-iam-policy-binding "$JOB_SCHEDULER" --region="$REGION" \
        --member="serviceAccount:$SA_INVOKER_EMAIL" --role=roles/run.invoker --format=none

    local trigger=(
        --location="$SCHEDULER_LOCATION" --schedule='* * * * *' --time-zone=Etc/UTC
        --uri="https://run.googleapis.com/v2/projects/$PROJECT_ID/locations/$REGION/jobs/$JOB_SCHEDULER:run"
        --http-method=POST --oauth-service-account-email="$SA_INVOKER_EMAIL"
        --attempt-deadline=60s --max-retry-attempts=0
        --description="Runs php artisan schedule:run every minute (routes/console.php)"
    )
    if exists "${G[@]}" scheduler jobs describe "$SCHEDULER_TRIGGER" --location="$SCHEDULER_LOCATION"; then
        run "${G[@]}" scheduler jobs update http "$SCHEDULER_TRIGGER" "${trigger[@]}"
    else
        run "${G[@]}" scheduler jobs create http "$SCHEDULER_TRIGGER" "${trigger[@]}"
    fi
}

ensure_github_deployer() {
    log "Workload Identity Federation for GitHub Actions ($GITHUB_REPOSITORY, environment $ENVIRONMENT)"
    if ! exists "${G[@]}" iam workload-identity-pools describe "$WIF_POOL" --location=global; then
        run "${G[@]}" iam workload-identity-pools create "$WIF_POOL" --location=global --display-name="GitHub Actions"
    fi
    # The attribute mapping is one comma-separated flag value.
    # shellcheck disable=SC2054
    local provider=(
        --workload-identity-pool="$WIF_POOL" --location=global --display-name="GitHub Actions"
        --issuer-uri=https://token.actions.githubusercontent.com
        --attribute-mapping=google.subject=assertion.sub,attribute.repository=assertion.repository,attribute.environment=assertion.environment,attribute.ref=assertion.ref
        --attribute-condition="assertion.repository == '$GITHUB_REPOSITORY'"
    )
    if exists "${G[@]}" iam workload-identity-pools providers describe "$WIF_PROVIDER" --workload-identity-pool="$WIF_POOL" --location=global; then
        run "${G[@]}" iam workload-identity-pools providers update-oidc "$WIF_PROVIDER" "${provider[@]}"
    else
        run "${G[@]}" iam workload-identity-pools providers create-oidc "$WIF_PROVIDER" "${provider[@]}"
    fi

    # Only jobs that run in this GitHub environment can act as the deployer;
    # production's environment requires a reviewer's approval.
    local subject="principal://iam.googleapis.com/projects/$PROJECT_NUMBER/locations/global/workloadIdentityPools/$WIF_POOL/subject/repo:$GITHUB_REPOSITORY:environment:$ENVIRONMENT"
    run "${G[@]}" iam service-accounts add-iam-policy-binding "$SA_DEPLOYER_EMAIL" \
        --member="$subject" --role=roles/iam.workloadIdentityUser --condition=None --format=none

    local deployer="serviceAccount:$SA_DEPLOYER_EMAIL"
    bind_project "$deployer" roles/run.developer
    bind_project "$deployer" "projects/$PROJECT_ID/roles/$BACKUP_ROLE"
    bind_project "$deployer" roles/serviceusage.serviceUsageConsumer
    local sa
    for sa in "$SA_ADMIN_EMAIL" "$SA_WORKER_EMAIL"; do
        run "${G[@]}" iam service-accounts add-iam-policy-binding "$sa" --member="$deployer" \
            --role=roles/iam.serviceAccountUser --condition=None --format=none
    done
    run "${G[@]}" artifacts repositories add-iam-policy-binding "$ARTIFACT_REPOSITORY" --location="$REGION" \
        --member="$deployer" --role=roles/artifactregistry.writer --format=none
    run gcloud --quiet projects add-iam-policy-binding "$FIREBASE_PROJECT_ID" --member="$deployer" \
        --role=roles/firebasehosting.admin --condition=None --format=none
}

monitoring_api() { # method, path, [json body]
    local token
    token="$(gcloud auth print-access-token)"
    curl -fsS -X "$1" "https://monitoring.googleapis.com/v3/projects/$PROJECT_ID/$2" \
        -H "Authorization: Bearer $token" -H "x-goog-user-project: $PROJECT_ID" \
        -H 'Content-Type: application/json' ${3:+--data "$3"}
}

notification_channel() {
    if [ "$DRY_RUN" = 1 ]; then
        note "+ e-mail notification channel for $ALERT_EMAIL"
        echo "projects/$PROJECT_ID/notificationChannels/CHANNEL_ID"
        return
    fi
    local name
    name="$(monitoring_api GET 'notificationChannels?filter=type%3D%22email%22' \
        | jq -r --arg email "$ALERT_EMAIL" '[.notificationChannels[]? | select(.labels.email_address == $email) | .name][0] // empty')"
    if [ -z "$name" ]; then
        name="$(monitoring_api POST notificationChannels "$(jq -n --arg email "$ALERT_EMAIL" --arg env "$ENVIRONMENT" \
            '{type: "email", displayName: ("NavigateEd " + $env + " alerts"), labels: {email_address: $email}}')" | jq -r .name)"
    fi
    echo "$name"
}

ensure_uptime_check() { # display name, path; prints the check id
    local id
    id="$("${G[@]}" monitoring uptime list-configs --filter="displayName=\"$1\"" --format='value(name)' 2>/dev/null | head -n1 || true)"
    if [ -z "$id" ]; then
        run "${G[@]}" monitoring uptime create "$1" --resource-type=uptime-url \
            --resource-labels="host=$PANEL_HOST,project_id=$PROJECT_ID" \
            --protocol=https --path="$2" --period=5 --timeout=10 --status-classes=2xx >&2
        id="$("${G[@]}" monitoring uptime list-configs --filter="displayName=\"$1\"" --format='value(name)' 2>/dev/null | head -n1 || true)"
    else
        run "${G[@]}" monitoring uptime update "${id##*/}" --path="$2" --period=5 --timeout=10 >&2
    fi
    id="${id##*/}"
    echo "${id:-CHECK_ID}"
}

apply_policy() { # display name, JSON
    local file existing
    file="$(mktemp)"
    printf '%s\n' "$2" >"$file"
    existing="$("${G[@]}" monitoring policies list --filter="displayName=\"$1\"" --format='value(name)' 2>/dev/null | head -n1 || true)"
    [ "$DRY_RUN" = 0 ] || jq . "$file" | sed 's/^/      /' >&2
    if [ -n "$existing" ]; then
        run "${G[@]}" monitoring policies update "$existing" --policy-from-file="$file"
    else
        run "${G[@]}" monitoring policies create --policy-from-file="$file"
    fi
    rm -f "$file"
}

ensure_monitoring() {
    log "Monitoring: uptime checks, alerts and Error Reporting"
    local channel prefix="NavigateEd $ENVIRONMENT"
    channel="$(notification_channel)"

    # Error Reporting reads the JSON logs' stack traces (LOG_CHANNEL=json); its
    # "new error" e-mails are switched on in the console (environments.md).
    local check path label spec
    for spec in "panel:/login" "API:/up" "worker:/up/worker"; do
        label="${spec%%:*}"
        path="${spec#*:}"
        check="$(ensure_uptime_check "$prefix $label ($path)" "$path")"
        apply_policy "$prefix: $label is down" "$(jq -n --arg name "$prefix: $label is down" --arg check "$check" --arg channel "$channel" --arg path "$path" '{
            displayName: $name,
            documentation: {content: ("The uptime check on " + $path + " is failing. engineering/runbooks/incident.md"), mimeType: "text/markdown"},
            combiner: "OR",
            conditions: [{
                displayName: ("Uptime check " + $path + " failing"),
                conditionThreshold: {
                    filter: ("metric.type=\"monitoring.googleapis.com/uptime_check/check_passed\" AND resource.type=\"uptime_url\" AND metric.label.check_id=\"" + $check + "\""),
                    aggregations: [{alignmentPeriod: "1200s", perSeriesAligner: "ALIGN_NEXT_OLDER", crossSeriesReducer: "REDUCE_COUNT_FALSE", groupByFields: ["resource.label.*"]}],
                    comparison: "COMPARISON_GT", thresholdValue: 1, duration: "60s", trigger: {count: 1}
                }
            }],
            notificationChannels: [$channel]
        }')"
    done

    # Errors above a threshold: a log-based metric counting error lines from
    # the three roles, alerted on when it passes ERROR_ALERT_THRESHOLD in 5 min.
    local metric=navigateed_error_entries
    local filter="severity>=ERROR AND ((resource.type=\"cloud_run_revision\" AND resource.labels.service_name=(\"$SERVICE_ADMIN\" OR \"$SERVICE_WORKER\")) OR (resource.type=\"cloud_run_job\" AND resource.labels.job_name=\"$JOB_SCHEDULER\"))"
    if exists "${G[@]}" logging metrics describe "$metric"; then
        run "${G[@]}" logging metrics update "$metric" --log-filter="$filter"
    else
        run "${G[@]}" logging metrics create "$metric" --log-filter="$filter" \
            --description="Error and worse log lines from the NavigateEd panel, worker and scheduler"
    fi
    apply_policy "$prefix: errors above threshold" "$(jq -n --arg name "$prefix: errors above threshold" --arg metric "$metric" --arg channel "$channel" --argjson threshold "$ERROR_ALERT_THRESHOLD" '{
        displayName: $name,
        documentation: {content: "More error log lines than usual. Open Error Reporting for the grouped errors. engineering/runbooks/incident.md", mimeType: "text/markdown"},
        combiner: "OR",
        conditions: [{
            displayName: ("More than " + ($threshold | tostring) + " errors in 5 minutes"),
            conditionThreshold: {
                filter: ("metric.type=\"logging.googleapis.com/user/" + $metric + "\""),
                aggregations: [{alignmentPeriod: "300s", perSeriesAligner: "ALIGN_SUM", crossSeriesReducer: "REDUCE_SUM"}],
                comparison: "COMPARISON_GT", thresholdValue: $threshold, duration: "0s", trigger: {count: 1}
            }
        }],
        notificationChannels: [$channel]
    }')"

    # Failed jobs: one notification per failure line (at most one every 5 min).
    apply_policy "$prefix: queued job failed" "$(jq -n --arg name "$prefix: queued job failed" --arg channel "$channel" '{
        displayName: $name,
        documentation: {content: "A queued job used up its tries. Find it with jsonPayload.message=\"Queued job failed\"; the job id is in the entry. engineering/runbooks/incident.md", mimeType: "text/markdown"},
        combiner: "OR",
        conditions: [{
            displayName: "Queued job failed log line",
            conditionMatchedLog: {filter: "jsonPayload.message=\"Queued job failed\""}
        }],
        alertStrategy: {notificationRateLimit: {period: "300s"}, autoClose: "1800s"},
        notificationChannels: [$channel]
    }')"

    apply_policy "$prefix: scheduler runs failing" "$(jq -n --arg name "$prefix: scheduler runs failing" --arg job "$JOB_SCHEDULER" --arg channel "$channel" '{
        displayName: $name,
        documentation: {content: "Runs of the scheduler job are failing; scheduled tasks are not happening. engineering/runbooks/incident.md", mimeType: "text/markdown"},
        combiner: "OR",
        conditions: [{
            displayName: "More than 2 failed scheduler runs in 10 minutes",
            conditionThreshold: {
                filter: ("metric.type=\"run.googleapis.com/job/completed_execution_count\" AND resource.type=\"cloud_run_job\" AND resource.label.job_name=\"" + $job + "\" AND metric.label.result=\"failed\""),
                aggregations: [{alignmentPeriod: "600s", perSeriesAligner: "ALIGN_SUM", crossSeriesReducer: "REDUCE_SUM"}],
                comparison: "COMPARISON_GT", thresholdValue: 2, duration: "0s", trigger: {count: 1}
            }
        }],
        notificationChannels: [$channel]
    }')"

    apply_policy "$prefix: worker not running" "$(jq -n --arg name "$prefix: worker not running" --arg service "$SERVICE_WORKER" --arg channel "$channel" '{
        displayName: $name,
        documentation: {content: "The worker service has had no instance for 10 minutes; queued mail and pushes wait. engineering/runbooks/incident.md", mimeType: "text/markdown"},
        combiner: "OR",
        conditions: [{
            displayName: "No worker instance for 10 minutes",
            conditionAbsent: {
                filter: ("metric.type=\"run.googleapis.com/container/instance_count\" AND resource.type=\"cloud_run_revision\" AND resource.label.service_name=\"" + $service + "\""),
                aggregations: [{alignmentPeriod: "300s", perSeriesAligner: "ALIGN_MAX", crossSeriesReducer: "REDUCE_SUM"}],
                duration: "600s", trigger: {count: 1}
            }
        }],
        notificationChannels: [$channel]
    }')"
}

summary() {
    log "Done. Values for the GitHub environment \"$ENVIRONMENT\" (Settings > Environments):"
    cat >&2 <<EOF
    Variables:
      GCP_PROJECT_ID         $PROJECT_ID
      GCP_REGION             $REGION
      FIREBASE_PROJECT_ID    $FIREBASE_PROJECT_ID
      PANEL_URL              $PANEL_URL
    Secrets:
      GCP_WIF_PROVIDER       projects/$PROJECT_NUMBER/locations/global/workloadIdentityPools/$WIF_POOL/providers/$WIF_PROVIDER
      GCP_DEPLOY_SA          $SA_DEPLOYER_EMAIL

    The services run Google's sample image until the first deploy.
    Next: engineering/runbooks/environments.md, "After provisioning".
EOF
}

enable_apis
ensure_artifact_registry
ensure_service_accounts
ensure_secrets
ensure_sql
ensure_bucket
grant_runtime_roles
ensure_run_services
ensure_scheduler
ensure_github_deployer
ensure_monitoring
summary
