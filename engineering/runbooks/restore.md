# Restore

Restoring the database or uploaded files from backup, and the restore drill the Phase 0 gate requires: **yesterday's production backup restored into staging, and the panel signs in.**

**Who.** Restoring production over itself deletes the data written since the backup, so it is the product owner's decision (Build Bible section 4: deleting production data waits for the product owner). Restoring into staging and the drill are the Release role's.

**Status.** Not yet executed. The restore drill has not been run: it needs staging and production as described in [environments.md](environments.md). Record each drill in the table at the end.

## What is backed up

| Data | Backup | Kept | Where |
| --- | --- | --- | --- |
| Production database | Automated daily backup at 01:00 UTC; on-demand backup before every production deploy | 14 automated backups; on-demand ones until deleted | Cloud SQL, `navigateed-mysql` in `navigateed-d8c06` |
| Production database, any moment | Point-in-time recovery from binary logs | 7 days | same |
| Staging database | Automated daily backup | 7 | `navigateed-mysql` in `navigateed-staging` |
| Production uploads | Object versioning: a replaced or deleted file's previous version | 30 days; plus Cloud Storage soft delete (7 days) for deleted objects | `gs://navigateed-d8c06-admin` |
| Secrets | Secret Manager keeps every version | until destroyed | each project |
| Code and images | git tags; Artifact Registry keeps every `v*` image | always | GitHub, Artifact Registry |

List production backups:

```bash
gcloud sql backups list --instance=navigateed-mysql --project=navigateed-d8c06 --limit=10 \
  --format='table(id,windowStartTime,type,status,description)'
```

## Restore production in place (data corruption)

Only with the product owner's go-ahead. Everything written after the restore point is lost.

1. Stop writes. Close the panel to the internet for the duration (users and apps get an error until step 4), pause the scheduler and stop the worker:
   ```bash
   P=navigateed-d8c06
   gcloud scheduler jobs pause navigateed-scheduler-every-minute --location=africa-south1 --project=$P
   gcloud run services update-traffic navigateed-admin --clear-tags --region=africa-south1 --project=$P
   gcloud run services update-traffic navigateed-worker --clear-tags --region=africa-south1 --project=$P
   gcloud run services update navigateed-admin --scaling=0 --region=africa-south1 --project=$P
   gcloud run services update navigateed-worker --scaling=0 --region=africa-south1 --project=$P
   ```
   Save the service configurations and traffic/tag state before changing them.
   Cancel running scheduler-job executions and wait for in-flight work and SQL
   sessions to finish. A minimum instance count of zero does not stop a worker.
   Tagged revisions can remain active under manual zero scaling, so clear their
   tags as shown and verify no instances or database consumers remain.
2. Take an on-demand backup of the current (bad) state, so nothing is lost for good:
   `gcloud sql backups create --instance=navigateed-mysql --project=navigateed-d8c06 --description="Before restore"`
3. Restore, either a backup:
   ```bash
   gcloud sql backups restore <BACKUP_ID> --restore-instance=navigateed-mysql --project=navigateed-d8c06
   ```
   or a point in time, into a new instance (safer: the original stays as it is until you switch):
   ```bash
   gcloud sql instances clone navigateed-mysql navigateed-mysql-restored --point-in-time='2026-11-02T08:15:00Z' --project=navigateed-d8c06
   ```
   then point the services at it (`SQL_INSTANCE` in `infra/gcp/env/production.env`, re-run `provision.sh`).
4. Restore the recorded service configurations, including scaling mode and
   traffic/tag assignments. For the configured automatic mode use `--scaling=auto`
   on both services; restoring only `--min-instances` leaves manual zero scaling
   in place. Verify the panel and worker, then resume the scheduler trigger.
5. Smoke test, then tell the schools what period of data was lost.

## Restore an uploaded file

```bash
gcloud storage ls --all-versions gs://navigateed-d8c06-admin/<path>       # versions with generation numbers
gcloud storage cp gs://navigateed-d8c06-admin/<path>#<generation> gs://navigateed-d8c06-admin/<path>
```

A deleted object without versioning can be restored for 7 days: `gcloud storage restore gs://navigateed-d8c06-admin/<path>#<generation>`.

## Restore production into staging (the drill, and migration rehearsals)

This temporarily copies production personal data. Only operators already
permitted to read production may access the destination. Keep automatic staging
deployments paused throughout the window, including rollback: a new deployment
must not restart a consumer on the copied data. Never copy production secrets,
uploads or `.env` files into staging.

The additional staging/email ceiling is **US$10/month**, excluding the existing
production bill. Inventory current resources and remaining spend before the
window. Include temporary disk increases, backups, network and instance time in
the estimate. Do not create resources, enlarge disks or resume an always-on
worker without a verified plan within that ceiling. If there is no feasible
isolated destination, record the drill as pending and continue independent
implementation and tests. Do not reduce isolation to fit the price.

### 1. Quarantine staging before copying any production data

Save the current service configuration, image, scaling mode, ingress, IAM,
traffic/tag assignments and deployment/scheduler state in a private operator
workspace outside the repository. Use `umask 077`; exports can contain sensitive
environment values. Record how to restore them before changing anything.

Pause the staging deployment workflow and any other automatic deployers. Pause
the trigger, remove revision tags and disable both database-connected services:

```bash
S=navigateed-staging
gcloud scheduler jobs pause navigateed-scheduler-every-minute --location=africa-south1 --project=$S
gcloud run services update-traffic navigateed-admin --clear-tags --region=africa-south1 --project=$S
gcloud run services update-traffic navigateed-worker --clear-tags --region=africa-south1 --project=$S
gcloud run services update navigateed-admin --scaling=0 --region=africa-south1 --project=$S
gcloud run services update navigateed-worker --scaling=0 --region=africa-south1 --project=$S
gcloud run jobs executions list --job=navigateed-scheduler --region=africa-south1 --project=$S
```

Cancel each running scheduler execution with `gcloud run jobs executions cancel
<EXECUTION> --region=africa-south1 --project=$S`, then verify it has stopped. Check
for other consumers from the inventory, including manual jobs and tagged
revisions. Confirm zero active application/worker instances and no application
SQL sessions before continuing. Restrict destination access to authorised
operators for the whole window.

`--min-instances=0` is a lower bound, not a stop. Cloud Run manual zero scaling
disables the service, but tagged revisions can remain accessible independently;
clear tags and verify the actual state. See [Google's service-disable guidance](https://docs.cloud.google.com/run/docs/configuring/services/manual-scaling#disable-service).
Do not start a replacement worker, scheduler or web revision until sanitisation
is complete. Keep the scheduler and worker disabled during the inspection too.

### 2. Back up staging, then choose and restore the production backup

Confirm the target project is `navigateed-staging` and differs from the source
`navigateed-d8c06`. Confirm the captured configurations, paused deployments,
stopped executions and zero consumers. These checks protect this data-copy
operation; they do not block other development.

```bash
gcloud sql backups create --instance=navigateed-mysql --project=$S --description="Before restore drill"
gcloud sql backups list --instance=navigateed-mysql --project=navigateed-d8c06 \
  --filter="type=AUTOMATED AND status=SUCCESSFUL" --limit=2 --format='table(id,windowStartTime)'
```

Record the staging backup id and yesterday's successful production backup id.
The destination must have enough capacity; a resize needs its own cost check
against the same US$10 ceiling. Leave the drill pending if it cannot fit.

Only after the quarantine evidence exists, restore into the staging instance:

```bash
gcloud sql backups restore <PRODUCTION_BACKUP_ID> --restore-instance=navigateed-mysql \
  --backup-instance=navigateed-mysql --backup-project=navigateed-d8c06 --project=$S
```

This replaces staging's databases and users. Restore the staging database user's
staging credential through the secure operator procedure or Cloud SQL console;
do not print secrets, place their values in command history or use production's
application credential. The services remain disabled.

### 3. Sanitise delivery and credentials while all consumers remain stopped

Use an operator connection to the staging database. Check the schema first:
Phase 0 and Phase 1 versions may differ. Remove operational delivery intent from
the copy before starting application code. Retain the source backup unchanged.

```sql
UPDATE settings SET message = 'array' WHERE type = 'mail_mailer';
UPDATE settings SET message = '' WHERE type IN (
  'mail_host', 'mail_username', 'mail_password', 'project_id', 'sender_id', 'service_account_file',
  'stripe_secret_key', 'stripe_publishable_key', 'stripe_webhook_secret',
  'razorpay_api_key', 'razorpay_secret_key', 'razorpay_webhook_secret',
  'paystack_public_key', 'paystack_secret_key',
  'flutterwave_public_key', 'flutterwave_secret_key', 'flutterwave_hash_key', 'recaptcha_secret_key');
UPDATE settings SET message = '0' WHERE type IN
  ('online_payment', 'stripe_status', 'razorpay_status', 'paystack_status', 'flutterwave_status');
UPDATE users SET fcm_id = NULL, remember_token = NULL;
DELETE FROM jobs;
DELETE FROM failed_jobs;
DELETE FROM job_batches;
DELETE FROM domain_events WHERE dispatched_at IS NULL;
DELETE FROM personal_access_tokens;
DELETE FROM sessions;
DELETE FROM password_resets;
DELETE FROM password_reset_tokens;
UPDATE learner_invitations SET revoked_at = CURRENT_TIMESTAMP
  WHERE used_at IS NULL AND revoked_at IS NULL;
UPDATE guardian_invitations SET used_at = CURRENT_TIMESTAMP WHERE used_at IS NULL;
```

Apply the invitation-table statements only when those tables exist in the
restored version. Both password-reset tables exist in the current schema; check
older versions rather than skipping failed statements. Confirm each statement
succeeded and no pending queue/outbox work remains. Do not run a worker to drain
copied production jobs: they contain production recipients and authority.

The `array` mailer discards messages in memory. The `log` mailer writes private
message bodies and invitation links into logs and is unsuitable for this copy.
Verify environment/Secret Manager overrides too: staging must have no production
SES, FCM, payment or OAuth credentials. Disable external provider access for the
inspection. A cleaned settings row alone cannot neutralise an environment secret.
No production uploads are copied; missing photos/documents are expected.

### 4. Inspect with only the private web service enabled

Prepare the private web configuration from the captured image/configuration.
Keep ingress restricted to authorised operators, keep revision tags removed,
force `MAIL_MAILER=array`, and remove provider credential mounts for this window.
Use staging's app key, database password and admin password. Clear cached
configuration by starting a fresh revision after sanitisation. Do not restore
normal public ingress or the original full configuration yet.

For the copy only, set `ADMIN_EMAIL` to the authorised production admin account
being inspected and remove staging's `.admin-provisioned` marker. The entrypoint
then sets staging's admin password on that copied account. This does not change
the production account. Keep the password in Secret Manager; do not paste it into
logs or the drill record.

Enable only that prepared web service with `--scaling=1`. Leave the worker at
manual zero, scheduler trigger paused and deployments disabled. Verify the
mailer/credentials and access restrictions before opening the panel. Sign in,
open Home and a learner page, compare counts and rehearse migrations. Record
results without personal information. Worker smoke/observation is a separate
check on demo data; it is intentionally not part of this copied-data inspection.

### 5. Put staging back before normal services resume

Disable the inspection web service with `--scaling=0`, wait for its instances
and SQL sessions to stop, and leave the worker/scheduler disabled. Restore the
recorded **staging** backup into the **staging** instance. Verify its database
credential and demo data before restoring the captured service configurations,
scaling mode, ingress and traffic/tag assignments. Manual zero scaling must be
cleared explicitly (`--scaling=auto` for the configured automatic mode).

Check staging's own admin sign-in and smoke test on the restored demo data, then
resume its trigger and deployments. Resume the worker only if its ongoing cost
fits the remaining monthly ceiling. If any step fails, leave the destination
quarantined and record the next action; do not reopen services on the copied data.

Record the copied-data window and cleanup of temporary credentials/logs and
recovery artifacts. Dispose of staging copies holding production data according
to the agreed drill cleanup/retention authorisation; preserve the production
source backup and its retention policy. No production data is deleted by these
staging instructions.

## Drill record

| Date | Production backup id (taken) | Restored into | Panel signed in | Time taken | Run by | Notes |
| --- | --- | --- | --- | --- | --- | --- |
| Not yet run | | | | | | Needs credentials, a feasible US$10 plan, pre-copy quarantine evidence and staging |

## See also

[rollback.md](rollback.md) · [release.md](release.md) · [environments.md](environments.md) · [incident.md](incident.md)
