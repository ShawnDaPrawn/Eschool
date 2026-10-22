# Release

How a phase (or a hot fix) gets to production. The ten steps below run in order: tag the release, build the image, back up production, prove the release on staging, rehearse the migrations against a copy of production data, deploy, check every profile, watch for an hour, and tell people. The Build Bible, Appendix D, is the outline we follow.

**Who.** The Release role runs it; the product owner approves the production deployment in GitHub. Production gets a phase only once its gate has passed and the product owner has signed it off on staging (the Build Bible, section 4). A hot fix for something broken in production skips the gate, but not the steps, and the product owner hears about it the same day.

**Status.** Not yet executed: no release has gone through this runbook. The first one is Phase 0's (`v0.1.0`), after [environments.md](environments.md) step 7.

## Before you start

- [ ] The phase gate is closed in the build log: the phase plan's features delivered or deferred with a reason, the security record (`engineering/security/phase-N.md`) and the self-test record complete with nothing failing, the UX scripts run, the k6 smoke (`k6-smoke.yml`) within budget on staging, and the demo and the product owner's sign-off recorded.
- [ ] `CHANGELOG.md`: the Unreleased section becomes `## vX.Y.Z (YYYY-MM-DD)`, with a fresh empty Unreleased above it.
- [ ] Appendices A, B and E, the wiki and the user docs updated.
- [ ] `main` is green and staging runs the commit you will tag.
- [ ] App links (once per signing key, then whenever it changes): in `e-school/web/.well-known/assetlinks.json` and `e-school-teacher/web/.well-known/assetlinks.json`, `RELEASE_SIGNING_SHA256_FINGERPRINT` is replaced by the SHA-256 of the key that signs the store build (Play Console > App integrity > App signing key certificate; add the upload key's too if internal builds are side-loaded); in both `apple-app-site-association` files `APPLE_TEAM_ID` is replaced by the team id, and Associated Domains is on for both App IDs in the Apple developer account. Check after the deploy: `curl -s https://app.navigateed.co.za/.well-known/assetlinks.json` answers the file, and `apple-app-site-association` answers `Content-Type: application/json`. See [Mobile-App-Configuration](../../wiki/Mobile-App-Configuration.md#deep-links).

## Steps

### 1. Tag

Versions: `v0.<phase>.<patch>` until the platform's first pilot (Phase 0 is `v0.1.0`, a hot fix on it `v0.1.1`); `v1.0.0` at the pilot.

```bash
git checkout main && git pull
git tag -a v0.1.0 -m "Phase 0: Groundwork"
git push origin v0.1.0
```

Pushing the tag starts **Deploy production**, which waits for approval. Do not approve yet.

### 2. Build image

The production workflow builds `admin-panel:<commit>` and `admin-panel:v0.1.0` from the tag after approval. Staging already runs the same commit from the merge to `main`, so what you test on staging is what production gets.

### 3. Back up production

The workflow takes an on-demand backup before deploying. Take one yourself now as well, so the rehearsal in step 5 uses a fresh copy:

```bash
gcloud sql backups create --instance=navigateed-mysql --project=navigateed-d8c06 --description="Release v0.1.0 rehearsal"
gcloud sql backups list --instance=navigateed-mysql --project=navigateed-d8c06 --limit=1
```

### 4. Deploy to staging and smoke test

Already done by the merge. Confirm: **Actions > Deploy staging** is green for the tagged commit, and `infra/deploy/smoke.sh https://<staging panel>` passes.

### 5. Rehearse the migrations against a copy of production

This proves the release's migrations run on real data, at real size, in acceptable time. It puts production data in staging for the duration: follow [restore.md](restore.md), "Restore production into staging", which also makes staging unable to e-mail or push to real people, then:

1. Restore the backup from step 3 into staging (restore.md).
2. Deploy the release commit to staging again (**Actions > Deploy staging > Run workflow**); the web role runs the new migrations on the copy.
3. Record how long the migrations took (the revision's startup logs) and that the smoke test passed.
4. Put staging back to its own data (restore.md, "Afterwards").

If a migration fails or takes longer than a few minutes, stop: fix it (a long back-fill becomes a queued job) and start again from step 1 with a new tag.

### 6. Deploy production

Approve the waiting **Deploy production** run (Actions > the run > Review deployments > production > Approve). It backs up, builds, deploys the panel, worker and scheduler, and Hosting.

### 7. Run migrations through the entrypoint

Nothing to do by hand: the panel's new revision runs `php artisan migrate --force` on start (docker/migrate.php, one instance at a time). Check its startup logs show the expected migrations and no error.

After a release that mounts secrets for the first time (the first one after Phase 0 in each environment, or after adding a value to a secret listed in [environments.md](environments.md), "Secrets"), and only once `infra/gcp/provision.sh` has mounted them: run `php artisan secrets:audit-settings --purge` as an artisan execution of the scheduler job (the commands are in environments.md, "Secrets"). It clears the `settings` rows the environment now replaces, so no key stays in the database.

### 8. Smoke test every profile

- The workflow's smoke job must be green.
- By hand on production, one short path per profile touched by the release: school admin signs in and opens Home; teacher opens today's timetable (app and teach portal); learner opens assignments; parent switches child and opens fees. Use the staff test accounts, never a real family's.
- `k6-smoke.yml` against production with low load is optional; never with production user tokens.

### 9. Watch errors for an hour

- Error Reporting: no new error groups since the deploy.
- Monitoring alerts: none open.
- `/up/worker` stays `ok`; the scheduler job runs every minute.
- Logs: `severity>=ERROR` for `navigateed-admin` and `navigateed-worker` stays at its usual level.

If users are affected, roll back ([rollback.md](rollback.md)) and investigate on staging.

### 10. Announce

To the product owner and the pilot schools' contacts: the version, what changed for them in plain words (from the changelog), and anything they need to do (for example an app update). Record the release in the Build log (Appendix E) and in `wiki/Release-History.md`.

## Rollback in short

Redeploy the previous image ([rollback.md](rollback.md)). Migrations are additive, so the previous code still runs on the new schema and a down-migration is rarely needed. A backup is restored only when data is damaged, through [restore.md](restore.md).
