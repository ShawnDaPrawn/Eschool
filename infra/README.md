# Infrastructure

Scripts that create and operate NavigateEd's cloud environments. The application image itself is built from [`admin-panel/Dockerfile`](../admin-panel/Dockerfile); the deploy workflows are in [`.github/workflows/`](../.github/workflows).

| Path | What it does |
| --- | --- |
| [`gcp/provision.sh`](gcp/provision.sh) | Creates or updates one environment (`staging` or `production`) on Google Cloud in `africa-south1`: Cloud SQL, bucket, secrets (names only), service accounts, Cloud Run services and the scheduler job, Workload Identity Federation for GitHub Actions, monitoring. Idempotent; `--dry-run` prints the changes. |
| [`gcp/env/`](gcp/env) | Per-environment settings for the script: project, sizes, names, hostnames. Never secret values. |
| [`gcp/artifact-cleanup-policy.json`](gcp/artifact-cleanup-policy.json), [`gcp/bucket-lifecycle.json`](gcp/bucket-lifecycle.json) | Image retention in Artifact Registry; how long replaced uploads are kept. |
| [`deploy/smoke.sh`](deploy/smoke.sh) | Smoke checks after a deploy (panel, API, worker, portals). |
| [`firebase/deploy-hosting.sh`](firebase/deploy-hosting.sh) | Deploys the Firebase Hosting sites in `firebase.json`, mapping each target to the environment's site. |

How to use them: [`engineering/runbooks/environments.md`](../engineering/runbooks/environments.md) (setup) and [`deploy.md`](../engineering/runbooks/deploy.md).

**Status.** Written in Phase 0 without cloud credentials: checked with `bash -n`, shellcheck and `provision.sh --dry-run`, and `smoke.sh` was run against a local panel. Nothing here has run against Google Cloud yet.
