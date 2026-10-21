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

## Checklist (Build Bible, Appendix D)

- [ ] Spec linked; acceptance tests in it pass
- [ ] Follows the domain recipe (or the Holiday recipe for a legacy page)
- [ ] Migrations reversible; `school_id`, dates, cents where they apply
- [ ] Four tests per endpoint plus tenancy; policy tests; app tests; Playwright for panel pages
- [ ] OpenAPI, generated models, every language file, wiki and user docs updated
- [ ] Module switch and notification types registered
- [ ] No raw colour, no renamed v1 field, no `env()` outside config, no key in a client
- [ ] CI green; Review role approved

## Test plan

<!-- Commands run and their results; self-test rows added to engineering/self-tests/phase-N.md -->

- [ ]

## Deploy notes

<!-- Anything a deployer must do: new env vars or secrets, a migration that needs a back-fill job, a provisioning change. Write "None" if there is nothing. -->
