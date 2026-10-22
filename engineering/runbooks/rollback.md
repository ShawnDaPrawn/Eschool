# Rollback

Puts the previous version back when a release misbehaves. The default is to send traffic back to the previous revision, which takes seconds; redeploying an older tag and restoring data are the slower options below it.

**Status.** Not yet executed. The commands are checked against the gcloud reference; the first rollback drill should be run on staging during Phase 0's gate.

## Decide

| Situation | Do |
| --- | --- |
| The new code misbehaves (errors, broken page, wrong behaviour), data is fine | Option 1: previous revision |
| A new revision never became ready (deploy step failed) | Nothing: the previous revision is still serving. Fix forward |
| You need a specific older release, or the previous revision is gone | Option 2: redeploy a tag |
| Data is wrong or lost (a bad migration, a bug that wrote nonsense) | Stop writes (option 1 first), then [restore.md](restore.md) |

Migrations are additive, so the previous code runs on the new schema; a down-migration is not needed in the common case and should not be run on production without the product owner.

## Option 1: back to the previous revision (seconds)

```bash
PROJECT=navigateed-d8c06 REGION=africa-south1

# The newest first; the second line is the one to go back to.
gcloud run revisions list --service=navigateed-admin --region=$REGION --project=$PROJECT --limit=3
gcloud run services update-traffic navigateed-admin --region=$REGION --project=$PROJECT --to-revisions=<previous-revision>=100

gcloud run revisions list --service=navigateed-worker --region=$REGION --project=$PROJECT --limit=3
gcloud run services update-traffic navigateed-worker --region=$REGION --project=$PROJECT --to-revisions=<previous-revision>=100
```

The worker serves no requests, but traffic decides which revision Cloud Run keeps running, so the same command switches the worker back.

The scheduler job has no revisions; point it at the previous image (its digest is on the previous revision):

```bash
gcloud run revisions describe <previous navigateed-admin revision> --region=$REGION --project=$PROJECT --format='value(spec.containers[0].image)'
gcloud run jobs update navigateed-scheduler --image=<that image> --region=$REGION --project=$PROJECT
```

While traffic is pinned to a revision, a new deploy does not take traffic automatically. When the fix is released, return to the latest: `gcloud run services update-traffic navigateed-admin --to-latest --region=$REGION --project=$PROJECT` (and the same for the worker).

## Option 2: redeploy an older release

**Actions > Deploy production > Run workflow**, choose the older tag under "Use workflow from", approve. It takes a fresh backup, then deploys that tag's code to all three roles and Hosting. This also restores the older web portals and docs, which option 1 does not.

On staging, run **Deploy staging** from the older commit, or use option 1.

## Afterwards

- Smoke test: `infra/deploy/smoke.sh https://<panel>`. The `X-Request-Id` check passes for any build from Phase 0 on.
- Tell the product owner what was rolled back and why, the same day.
- Open an issue for the defect; the fix goes through staging and a new patch tag.
- If the rolled-back release added migrations, leave them in place; the next release includes them again.

## See also

[release.md](release.md) · [restore.md](restore.md) · [incident.md](incident.md) · [deploy.md](deploy.md)
