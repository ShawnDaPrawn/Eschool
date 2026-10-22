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
   gcloud run services update navigateed-admin --ingress=internal --region=africa-south1 --project=$P
   gcloud scheduler jobs pause navigateed-scheduler-every-minute --location=africa-south1 --project=$P
   gcloud run services update navigateed-worker --min-instances=0 --region=africa-south1 --project=$P
   ```
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
4. Bring everything back: `gcloud run services update navigateed-admin --ingress=all --region=africa-south1 --project=$P`, the worker's minimum instances (re-run `provision.sh`, or `--min-instances=1`), and `gcloud scheduler jobs resume navigateed-scheduler-every-minute --location=africa-south1 --project=$P`.
5. Smoke test, then tell the schools what period of data was lost.

## Restore an uploaded file

```bash
gcloud storage ls --all-versions gs://navigateed-d8c06-admin/<path>       # versions with generation numbers
gcloud storage cp gs://navigateed-d8c06-admin/<path>#<generation> gs://navigateed-d8c06-admin/<path>
```

A deleted object without versioning can be restored for 7 days: `gcloud storage restore gs://navigateed-d8c06-admin/<path>#<generation>`.

## Restore production into staging (the drill, and migration rehearsals)

This copies real learners' and parents' data into staging. While it is there, staging is treated like production: only the people who may access production use it, and it must not be able to e-mail, push to or charge real people. Put staging back afterwards.

1. **Stop staging's background work and take its own backup**, so it can be put back:
   ```bash
   S=navigateed-staging
   gcloud scheduler jobs pause navigateed-scheduler-every-minute --location=africa-south1 --project=$S
   gcloud run services update navigateed-worker --min-instances=0 --region=africa-south1 --project=$S
   gcloud sql backups create --instance=navigateed-mysql --project=$S --description="Before restore drill"
   ```
2. **Pick yesterday's production backup** (the latest successful automated one):
   ```bash
   gcloud sql backups list --instance=navigateed-mysql --project=navigateed-d8c06 \
     --filter="type=AUTOMATED AND status=SUCCESSFUL" --limit=2 --format='table(id,windowStartTime)'
   ```
   Staging's disk must be at least as large as production's; if not, `gcloud sql instances patch navigateed-mysql --storage-size=<GB> --project=$S` first.
3. **Restore it into staging's instance** (replaces every database and user on staging's instance):
   ```bash
   gcloud sql backups restore <BACKUP_ID> --restore-instance=navigateed-mysql \
     --backup-instance=navigateed-mysql --backup-project=navigateed-d8c06 --project=$S
   ```
4. **Give staging's database user its staging password back** (the restore brought production's users):
   ```bash
   gcloud sql users set-password <DB_USER> --host=% --instance=navigateed-mysql --project=$S \
     --password="$(gcloud secrets versions access latest --secret=navigateed-db-password --project=$S)"
   ```
   If production uses a different database user name from staging, create staging's user instead (`gcloud sql users create`).
5. **Cut staging off from real people.** In Cloud SQL Studio (Cloud Console > SQL > `navigateed-mysql` in `navigateed-staging` > Cloud SQL Studio, database `navigateed`), run:
   ```sql
   UPDATE settings SET message = 'log' WHERE type = 'mail_mailer';
   UPDATE settings SET message = '' WHERE type IN (
     'mail_host', 'mail_username', 'mail_password', 'project_id', 'sender_id', 'service_account_file',
     'stripe_secret_key', 'stripe_publishable_key', 'stripe_webhook_secret',
     'razorpay_api_key', 'razorpay_secret_key', 'razorpay_webhook_secret',
     'paystack_public_key', 'paystack_secret_key',
     'flutterwave_public_key', 'flutterwave_secret_key', 'flutterwave_hash_key', 'recaptcha_secret_key');
   UPDATE settings SET message = '0' WHERE type IN ('online_payment', 'stripe_status', 'razorpay_status', 'paystack_status', 'flutterwave_status');
   UPDATE users SET fcm_id = NULL;
   DELETE FROM personal_access_tokens;
   DELETE FROM sessions;
   ```
   (After work stream S6 moves these keys into Secret Manager, the staging project's secrets are already staging's own and the `settings` lines that no longer exist simply change nothing.)
6. **Let staging's admin sign in without production's password.** Point the entrypoint's admin provisioning at production's admin account and let it set staging's admin password on it:
   ```bash
   gcloud run services update navigateed-admin --update-env-vars=ADMIN_EMAIL=<production admin e-mail> --region=africa-south1 --project=$S
   gcloud storage rm gs://navigateed-staging-admin/.admin-provisioned
   ```
   The update starts a new revision; its entrypoint finds the account, sets the password from staging's `navigateed-admin-password` secret and writes the marker again.
7. **Check**: open the staging panel, sign in with that e-mail and `gcloud secrets versions access latest --secret=navigateed-admin-password --project=$S`, open Home and a learner's page, and note the counts look like production's. Run `infra/deploy/smoke.sh <staging panel>`. Uploaded files are not copied, so photos and documents show as missing; that is expected.

### Afterwards: put staging back

```bash
gcloud sql backups list --instance=navigateed-mysql --project=$S --filter="description='Before restore drill'" --limit=1
gcloud sql backups restore <THAT_BACKUP_ID> --restore-instance=navigateed-mysql --project=$S
gcloud run services update navigateed-admin --update-env-vars=ADMIN_EMAIL=<staging ADMIN_EMAIL from env/staging.env> --region=africa-south1 --project=$S
gcloud run services update navigateed-worker --min-instances=1 --region=africa-south1 --project=$S
gcloud scheduler jobs resume navigateed-scheduler-every-minute --location=africa-south1 --project=$S
```

Then confirm the staging panel signs in with staging's own admin and the smoke test passes. Delete the on-demand backups that hold production data only when the product owner agrees they are no longer needed.

## Drill record

| Date | Production backup id (taken) | Restored into | Panel signed in | Time taken | Run by | Notes |
| --- | --- | --- | --- | --- | --- | --- |
| Not yet run | | | | | | Needs staging (environments.md steps 1 to 5) |

## See also

[rollback.md](rollback.md) · [release.md](release.md) · [environments.md](environments.md) · [incident.md](incident.md)
