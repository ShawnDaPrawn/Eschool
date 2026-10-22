# Release

How a phase (or a hot fix) reaches production, following the Build Bible's release outline (Appendix D): tag, build image, back up production, deploy to staging and smoke test, rehearse migrations on a staging copy of production, deploy production, run migrations through the entrypoint, smoke test every profile, watch errors for an hour, announce.

**Who.** The Release role runs it; the product owner approves the production deployment in GitHub. A production release happens only after the phase gate has passed and the product owner has accepted the phase on staging (Bible section 4). Hot fixes for a live defect do not wait for a gate but follow the same steps and are reported the same day.

**Status.** Not yet executed: no release has gone through this runbook. The first one is Phase 0's (`v0.1.0`), after [environments.md](environments.md) step 7.

## Before you start

- [ ] Phase exit checklist complete (Appendix D): every "what exists when done" item ticked, security checks recorded in `engineering/security/phase-N.md`, self-test matrix complete with no failed rows, UX scripts passed, performance budgets met on staging (`k6-smoke.yml`), demo recorded, product owner's acceptance on staging recorded.
- [ ] `CHANGELOG.md`: the Unreleased section becomes `## vX.Y.Z (YYYY-MM-DD)`, with a fresh empty Unreleased above it.
- [ ] Appendices A, B and E, the wiki and the user docs updated.
- [ ] `main` is green and staging runs the commit you will tag.

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

### 5. Rehearse the migrations on a staging copy of production

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

Redeploy the previous image ([rollback.md](rollback.md)). Migrations are additive, so the previous code runs on the new schema and no down-migration is needed in the common case. Restore from backup only on data corruption, through [restore.md](restore.md).
