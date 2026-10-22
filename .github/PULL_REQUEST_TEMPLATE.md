## Summary

<!-- What changed and why. Link the spec: engineering/specs/<module>/<feature>.md -->

Spec:

## Which project

- [ ] `admin-panel`
- [ ] `e-school`
- [ ] `e-school-teacher`
- [ ] `packages/eschool_core`
- [ ] Another package under `packages/` (name it):
- [ ] `documentation`
- [ ] `wiki`
- [ ] `engineering` (specs, decision records, runbooks, security, self-tests)
- [ ] Infrastructure and CI (`infra/`, `.github/`, `admin-panel/Dockerfile`, `admin-panel/docker/`, `docker-compose.yml`)

## Checklist

- [ ] The spec is linked and its acceptance tests pass
- [ ] New backend code uses the domain folders (a change to a legacy panel page may follow the Holiday example in the wiki's Contributing page)
- [ ] Every migration has a working `down()`; new tables have `school_id` and timestamps, and money is in cents
- [ ] Tests for the happy path, the wrong role, someone else's record and the tenancy suite; policy tests for new policies; app tests; Playwright for new panel pages
- [ ] `openapi.yaml` and the generated client, all language files, the wiki and the user docs are up to date
- [ ] Any new module switch or notification type is registered
- [ ] No raw colours, no v1 field renamed or removed, no `env()` outside `config/`, no key or secret in an app
- [ ] CI passes and a reviewer has approved

## Test plan

<!-- Commands run and their results; self-test rows added to engineering/self-tests/phase-N.md -->

- [ ]

## Deploy notes

<!-- Anything a deployer must do: new env vars or secrets, a migration that needs a back-fill job, a provisioning change. Write "None" if there is nothing. -->
