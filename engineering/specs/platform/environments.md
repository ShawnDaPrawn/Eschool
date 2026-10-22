# Environments and operations

Phase 0, feature 0.12 (with the infrastructure part of 0.4). Owner role: Release. Work stream S4.

Build Bible references: section 3.5 (services and environments), 5.6 (observability), Phase 0 steps 4 and 12, Appendix D (release runbook).

## User stories

| Profile | Story |
| --- | --- |
| Product owner | I can create staging and bring production up to the planned shape by running one script per environment, without anyone typing secret values into code or chat. |
| Product owner | Every merge to `main` reaches staging without anyone doing anything, so I can accept a phase on staging. |
| Product owner | Production changes only when I approve a tagged release, and a backup is taken first. |
| Product owner | I am e-mailed when the panel, the API or the worker is down, when errors spike, when a queued job fails for good, or when scheduled tasks stop running. |
| Engineer (Release, incident) | Every log line carries a request id, user id, school id and route, so I can follow one user's request, and the job it queued, from a screenshot of the `X-Request-Id` header. |
| Engineer | I can run the same image locally in its three roles with one command. |
| School admin, teacher, learner, parent | Mail and pushes leave the request path (Phase 0 step 4), and the worker and scheduler that deliver them are watched, so they keep arriving. Nothing I see changes. |

## Acceptance tests

| # | Given | When | Then | Proven by |
| --- | --- | --- | --- | --- |
| 1 | the image | it starts with no `CONTAINER_ROLE` | it behaves as before: migrates, provisions the admin, serves on 8080 | entrypoint review; `docker compose up` (not run here: no Docker daemon) |
| 2 | the image | `CONTAINER_ROLE=worker` | `queue:work --tries=3 --timeout=120 --sleep=3 --max-time=3600` runs, restarts when it exits, stops after the current job on SIGTERM, no migration runs | worker loop exercised with stand-in commands (restart, SIGTERM, health server) |
| 3 | the image | `CONTAINER_ROLE=scheduler` | one `schedule:run`, then exit | entrypoint review |
| 4 | several web instances start together | they migrate | one migrates, the others wait for the lock and find nothing to do | `docker/migrate.php` run against MariaDB with the lock held by another session |
| 5 | a request | it is answered | the response has `X-Request-Id`; a plain incoming id is kept, anything else replaced | `StructuredLoggingTest` |
| 6 | `LOG_CHANNEL=json` | code logs during a request | one JSON line with severity, message, UTC time, `request_id`, `user_id`, `school_id`, `route`, `method` | `StructuredLoggingTest` |
| 7 | a request body with names, e-mail, ID number, free text | it is passed as log context | none of it appears in the log; ids do | `StructuredLoggingTest::test_request_bodies_and_personal_data_never_reach_the_log` |
| 8 | an exception logged at error | it is formatted | Error Reporting's ReportedErrorEvent shape with a stack trace, no function arguments, no SQL bound values | `StructuredLoggingTest` |
| 9 | the database queue | a due job waits more than 10 minutes | `GET /up/worker` answers 503; otherwise 200 | `WorkerHealthTest` |
| 10 | a job fails its last try | the failure is recorded | one "Queued job failed" error line with the job's name and id, without its data | `WorkerHealthTest` |
| 11 | the GitHub variable `STAGING_DEPLOY_ENABLED` is not `'true'` | `main` is pushed | the staging deploy job is skipped, not failed | `deploy-staging.yml` condition; actionlint |
| 12 | a production deploy is started from a branch | it runs | it stops with an error before any cloud step | `deploy-production.yml` check job |
| 13 | a production deploy is started from a `v*` tag | a reviewer approves | backup, image, panel, worker, scheduler, Hosting, smoke test, in that order | `deploy-environment.yml`; not yet run |
| 14 | `provision.sh staging` | it is run twice | the second run creates nothing new and changes only settings | idempotent checks per resource; `--dry-run` reviewed; not yet run |
| 15 | `provision.sh production` | it is run without `CONFIRM_PRODUCTION=yes` | it refuses; it never writes a secret value or database password in production; a Cloud SQL restart needs `ALLOW_SQL_RESTART=yes` | dry run output |
| 16 | yesterday's production backup | it is restored into staging | the staging panel signs in | restore drill in `runbooks/restore.md`; **not yet run** |

## Data changes

No tables or columns. Configuration only:

- `config/logging.php`: channel `json`.
- `config/queue.php`: `connections.database.retry_after` 90 to 180; new `worker_health.max_wait_seconds` (`QUEUE_HEALTH_MAX_WAIT`, default 600).

## Endpoints

| Method and path | Middleware | Response |
| --- | --- | --- |
| `GET /up/worker` | `throttle:60,1`; outside the web and api groups (no session, no cookies, no installer redirect) | `200 {"status":"ok","oldest_wait_seconds":N}`, `200 {"status":"no-queue","oldest_wait_seconds":null}` when the queue is not the database driver, `503 {"status":"stalled",...}` |

Every response gains the `X-Request-Id` header. No v1 or v2 API change.

## Screens

None.

## Notification types

None. Alerts go to the product owner by e-mail from Cloud Monitoring, not through the Notifier.

## Module switch

None: platform infrastructure, always on.

## Security notes

- No secret value in the repository, env files, workflows or logs. Secret Manager holds them; staging values are generated by the script and piped straight in; production values are added only by the product owner.
- GitHub Actions authenticates with Workload Identity Federation; each environment's deployer can be impersonated only by jobs of `ShawnDaPrawn/Eschool` running in that GitHub environment. Production's environment requires a reviewer.
- Least-privilege service accounts per role; the deployer cannot read secrets or data; backups use a custom role with four permissions.
- The worker has no public ingress. `/up/worker` reveals only a status and a wait time.
- Logs carry ids only: the JSON formatter keeps numbers, booleans, id values and a few descriptive strings, and masks e-mail addresses, ID numbers and phone numbers in messages. `zend.exception_ignore_args` keeps function arguments out of traces.
- The restore drill copies production personal data into staging for its duration; the runbook cuts staging off from mail, push and payments first and puts staging back afterwards.

## Self-test steps

Rows for `engineering/self-tests/phase-0.md`, run on staging once it exists:

| Feature | Profile | Device or browser | Steps | Expected |
| --- | --- | --- | --- | --- |
| Environments | School admin | Chrome 1280 px | Sign in on staging; open Home; check the response headers | Page loads; `X-Request-Id` present; the id finds the request's log lines in Cloud Logging |
| Environments | (operator) | Cloud Console | Merge a change; watch Deploy staging | Deploy and smoke jobs green; new revision serving |
| Environments | (operator) | Cloud Console | Scheduler executions list | One successful execution per minute |
| Environments | (operator) | Browser | Open `/up/worker` | `{"status":"ok",...}` |
| Environments | (operator) | Cloud Console | Restore drill (runbooks/restore.md) | Staging panel signs in with production data; staging put back |
