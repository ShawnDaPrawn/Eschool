# Deploy

How code reaches staging and production, what the pipeline does, and how to check a deploy. For a phase release with its backup, rehearsal and announcement, follow [release.md](release.md); this page is the mechanics underneath.

**Status.** The workflows are written and pass actionlint, and the smoke script was run against a local panel. No deploy to Google Cloud has run yet: staging deploys start once [environments.md](environments.md) steps 1 to 5 are done.

## PHP2Shell security update (21 October 2026)

CVE-2026-109618 and CVE-2026-109619 require a PHP runtime update; application
validation and INI changes cannot fully remediate them. The shared image now
pins `php:8.3.36-apache-bookworm`, the first fixed 8.3 release. See the
[PHP security advisory](https://www.php.net/archive/2026.php#2026-10-21-5).

Rebuild with `--pull` and deploy the new image digest to **all three roles**:
`navigateed-admin`, `navigateed-worker` and `navigateed-scheduler`. Existing
containers retain the old PHP binary until replaced. Confirm that no traffic
or scheduled executions use the old image; do not roll back to a vulnerable
runtime. Follow the normal staging and production approval process below.

Before deploying, CI builds the image, checks `PHP_VERSION_ID >= 80336` on
PHP 8.3, checks Composer platform requirements and validates Apache config.
Run the existing application tests and deployment smoke checks as well. For
an independently managed Apache/FPM host, upgrade PHP through its vendor and
restart every PHP/Apache worker; updating this repository does not update
that host. Confirm the web runtime version, not only the CLI version.

## Who deploys what

| Target | Trigger | Approval | Backup first |
| --- | --- | --- | --- |
| Staging | every push to `main` (a merged pull request), or **Actions > Deploy staging > Run workflow** | none | no (staging data is disposable) |
| Production | pushing a `v*` tag, or **Actions > Deploy production > Run workflow** with a `v*` tag selected | a reviewer on the `production` environment (the product owner) | yes, an on-demand Cloud SQL backup |

Production is never deployed by a merge. The production workflow refuses any ref that is not a `v*` tag. A hot fix for a production defect follows the same path with a patch tag (`v0.1.1`) and are reported to the product owner the same day.

## What the pipeline does

`.github/workflows/deploy-staging.yml` and `deploy-production.yml` both call `.github/workflows/deploy-environment.yml`:

1. Checks the GitHub environment has `GCP_PROJECT_ID`, `GCP_WIF_PROVIDER` and `GCP_DEPLOY_SA`.
2. Authenticates to Google Cloud through Workload Identity Federation as `navigateed-deployer` (no stored key).
3. Production only: `gcloud sql backups create` on `navigateed-mysql`, and lists it.
4. Builds `admin-panel/Dockerfile` with `APP_RELEASE` set to the tag or commit, pushes it to Artifact Registry as `admin-panel:<commit>` (and `admin-panel:<tag>` for a tag), and deploys by digest.
5. Deploys `navigateed-admin`. Its new revision starts in the web role: config cache, migrations (one instance at a time, behind a MySQL lock), admin provisioning, `InstallationSeeder` and `EmailSeeder` (new roles, permissions and e-mail templates), config cache again, then Apache. Cloud Run sends traffic to it only once it listens; until then the previous revision serves. A failed migration fails the revision and the deploy stops here with the old revision still serving.
6. Deploys `navigateed-worker` (worker role) with the same image.
7. Updates the `navigateed-scheduler` job's image; the next minute's run uses it.
8. Builds the Flutter web portals with `API_BASE_URL`, `ENV` and `FLAVOR` defines, once `e-school/web` and `e-school-teacher/web` exist.
9. Deploys Firebase Hosting with `infra/firebase/deploy-hosting.sh`: each target in `firebase.json` goes to the site named by `FIREBASE_SITE_<TARGET>`; the docs build in the predeploy step.
10. Smoke test (separate job): `infra/deploy/smoke.sh` checks `/up`, the sign-in page, the `X-Request-Id` header (proof the new build serves), `/up/worker`, a public API read, and each portal.

Configuration (environment variables, secrets, instance counts, volumes) is not part of the deploy: it is set by `infra/gcp/provision.sh` and kept across deploys. Change it there.

## Order and compatibility

The web role migrates before the worker and scheduler get the new image, and the old web revision keeps serving during the migration. That is safe because migrations are additive (Bible 5.4): new columns and tables, never a rename or drop in the same release as the code that stops using them. A migration that needs a long back-fill runs it as a queued job, not inside the migration.

## Deploy by hand

Normally unnecessary. With `gcloud` signed in as an owner:

```bash
PROJECT=navigateed-staging REGION=africa-south1
IMAGE=$REGION-docker.pkg.dev/$PROJECT/navigateed/admin-panel
gcloud auth configure-docker $REGION-docker.pkg.dev
docker build --build-arg APP_RELEASE=$(git rev-parse --short HEAD) -t $IMAGE:manual admin-panel
docker push $IMAGE:manual
gcloud run deploy navigateed-admin --image=$IMAGE:manual --region=$REGION --project=$PROJECT
gcloud run deploy navigateed-worker --image=$IMAGE:manual --region=$REGION --project=$PROJECT
gcloud run jobs update navigateed-scheduler --image=$IMAGE:manual --region=$REGION --project=$PROJECT
infra/deploy/smoke.sh "$(gcloud run services describe navigateed-admin --region=$REGION --project=$PROJECT --format='value(status.url)')"
```

## Checking a deploy

- **Revisions**: `gcloud run revisions list --service=navigateed-admin --region=africa-south1 --project=<project> --limit=3`; the newest should be serving 100%.
- **Startup logs** of the new revision (migrations, provisioning): Cloud Logging, `resource.type="cloud_run_revision" AND resource.labels.service_name="navigateed-admin"`, or `gcloud logging read 'resource.labels.service_name="navigateed-admin"' --project=<project> --freshness=15m --limit=50`.
- **Worker**: `/up/worker` answers `{"status":"ok"}`; its logs show `queue:work` starting.
- **Scheduler**: `gcloud run jobs executions list --job=navigateed-scheduler --region=africa-south1 --project=<project> --limit=5` shows one successful execution a minute.
- **Errors**: Error Reporting for the project; anything new since the deploy started.

## When a deploy fails

| Where | What it means | Do |
| --- | --- | --- |
| "Check the environment is configured" | a variable or secret is missing in the GitHub environment | add it ([environments.md](environments.md), step 4) |
| Authenticate | the WIF provider or deployer binding does not match this repository and environment | re-run `provision.sh` for the environment; check the environment name |
| Back up the database | Cloud SQL busy (another operation) or missing permission | wait and re-run; production does not deploy without the backup |
| Deploy the panel | the new revision did not start: usually a failed migration or a missing secret value | read the revision's startup logs; the old revision still serves. Fix forward with a new commit, or see [rollback.md](rollback.md) |
| Worker or scheduler step | the web revision is already live with the new code | re-run the workflow; until then the worker runs the previous image, which must stay compatible (additive migrations) |
| Firebase Hosting | a `FIREBASE_SITE_*` variable is missing, or the site does not exist | add the variable or create the site ([environments.md](environments.md), step 3) |
| Smoke test | the deploy finished but something does not answer | treat as an incident: [incident.md](incident.md); roll back if users are affected |

## See also

[release.md](release.md) · [rollback.md](rollback.md) · [environments.md](environments.md) · [incident.md](incident.md)
