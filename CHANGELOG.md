# Changelog

What changed in NavigateEd, newest first. Each phase release gets a section when it reaches production; work merged since the last release sits under **Unreleased**. Entries are grouped as Added, Changed, Removed, Fixed and Security, and say what changed for the people who use or run the platform. Deployers' actions are marked **Deploy:**.

Earlier history is kept where it was written and is not repeated here:

- Upstream eSchool versions up to 3.5.0 (the codebase we bought): [`documentation/changelog/index.md`](documentation/changelog/index.md), published at https://shawndaprawn.github.io/Eschool/changelog/.
- This fork's work before NavigateEd Phase 0 (security hardening, tests and CI, dependency updates, translations, branding), with links to every commit and pull request: [`wiki/Release-History.md`](wiki/Release-History.md).

## Unreleased

Phase 0 (Groundwork), in progress on the phase branch.

### Added

- Engineering folder: the spec template, decision records (ADR 0001 to 0005) and the self-test template under `engineering/`, and this changelog.
- Structured JSON logs (`LOG_CHANNEL=json`) to stderr with a request id, user id, school id and route on every line, an `X-Request-Id` response header, and exceptions formatted for Cloud Error Reporting. Log context carries ids only, never request bodies; e-mail addresses, ID numbers and phone numbers in messages are masked.
- `GET /up/worker`: a health check that fails when a queued job has waited more than ten minutes, for the worker's uptime check. A job that uses up its tries is logged once as "Queued job failed" (by name, without its data) for alerting, and log lines written by a job carry its name and id.
- Container roles: the one Docker image runs as the web service (default), the queue worker (`CONTAINER_ROLE=worker`: `queue:work --tries=3 --timeout=120`, restarted hourly, stopped gracefully on SIGTERM) or the scheduler (`CONTAINER_ROLE=scheduler`: one `schedule:run`, for a Cloud Run Job started every minute). Only the web role migrates (one instance at a time, behind a MySQL lock) and runs the installer seeders.
- Local stack with Docker Compose at the repository root: MySQL 8, the panel, the worker and the scheduler, built from the same image as Cloud Run.
- Environments on Google Cloud: `infra/gcp/provision.sh staging|production` creates or updates Cloud SQL (backups; point-in-time recovery in production), the uploads bucket, Secret Manager secrets (names only), least-privilege service accounts, the Cloud Run services `navigateed-admin` and `navigateed-worker`, the `navigateed-scheduler` job with an every-minute Cloud Scheduler trigger, Workload Identity Federation for GitHub Actions, uptime checks and alerts. Not yet run against a real project.
- Deploy workflows: staging on every merge to `main` once the repository variable `STAGING_DEPLOY_ENABLED` is `true`; production from a `v*` tag after a reviewer approves, with a database backup first and smoke tests after. Runbooks for environments, deploy, release, rollback, restore (with the restore drill, not yet run) and incidents in `engineering/runbooks/`.
- A one-off `artisan` container role, run as an execution of the scheduler job, for maintenance commands such as `queue:retry`.
- Secret scanning with gitleaks over the whole history on every pull request, push to `main` and weekly, and Dependabot for Composer, pub (both apps and every package), npm (docs and the panel's Playwright project), Docker and GitHub Actions. The dependency audit also lists outdated Dart packages (`dart pub outdated`, informational). Threat model and the Phase 0 security record in `engineering/security/`.
- k6 smoke test (`engineering/perf/k6-smoke.js`) with the Bible's p95 budgets (300 ms for lists, 150 ms for `/api/v2/me/context` once it exists), run by hand against any environment from **Actions > k6 smoke**.

### Changed

- The database queue's `retry_after` is 180 seconds, longer than the worker's 120-second job timeout, so a slow job is not picked up by a second worker.
- PHP no longer puts function arguments into exception traces (`zend.exception_ignore_args`), so a password or name passed to a function cannot reach a log. The image adds the `pcntl` extension, which `queue:work` needs to enforce job timeouts and stop cleanly.

### Security

- Secret scan of the whole history: no credentials found. The Firebase Android API keys in `google-services.json` are public client identifiers and are allowlisted for that file and key format only; the product owner should confirm their restrictions in the Cloud Console (`engineering/security/phase-0.md`).

**Deploy:** set `LOG_CHANNEL=json`, `CACHE_DRIVER=database` and `QUEUE_CONNECTION=database` on Cloud Run and add the `navigateed-worker` service and `navigateed-scheduler` job (`infra/gcp/provision.sh` does all of this); see `engineering/runbooks/environments.md`.
