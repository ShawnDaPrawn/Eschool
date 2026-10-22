# Runbooks

Step-by-step procedures for operating NavigateEd. Each runbook says when to use it, who may run it, the exact commands, how to check the result, and what to do if a step fails. Keep them short enough to follow during an incident.

| Runbook | Use it to |
| --- | --- |
| [environments.md](environments.md) | See what exists in staging and production, provision an environment, and set up the GitHub variables, secrets and Workload Identity Federation the deploy workflows need |
| [deploy.md](deploy.md) | Deploy to staging (automatic on merge) or production (tagged release), and what the pipeline does |
| [release.md](release.md) | Cut a phase release: tag, back up, rehearse, deploy production, smoke test, watch, announce |
| [rollback.md](rollback.md) | Put the previous version back when a release misbehaves |
| [restore.md](restore.md) | Restore the database or uploaded files from backup, and run the restore drill |
| [incident.md](incident.md) | Respond to an alert: triage, where to look, common failures, communication |

The infrastructure these runbooks operate is defined in [`infra/gcp/`](../../infra/gcp) (provisioning) and [`.github/workflows/`](../../.github/workflows) (`deploy-staging.yml`, `deploy-production.yml`, `deploy-environment.yml`, `k6-smoke.yml`).

**Status.** Written in Phase 0 before the product owner added cloud credentials. Nothing in these runbooks has been executed against Google Cloud yet; each runbook marks the steps that still need their first real run.
