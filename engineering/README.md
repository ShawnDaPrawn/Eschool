# Engineering

The working records for building NavigateEd on the seed codebase. The product owner's Build Bible is the plan; it is private and stays out of this public repository. This folder holds our own records of what we build: plans, specs, decisions, runbooks and test evidence.

| What | Where |
| --- | --- |
| The plan for each phase and the questions it raised | `phases/phase-N.md`, `phases/phase-N-questions.md` |
| One spec per feature, written before the code | `specs/<module>/<feature>.md` |
| Decision records | `adr/NNNN-<title>.md` |
| Self-test records, with screenshots in `self-tests/evidence/` | `self-tests/phase-N.md` |
| Runbooks: deploy, rollback, restore, incident, release, environments | `runbooks/` |
| Threat model and each phase's security record | `security/` |
| Performance smoke test (k6) with our latency budgets | `perf/k6-smoke.js` |
| What exists so far: data model, module and notification catalogues, build log | `bible/` |

Release notes go in [`CHANGELOG.md`](../CHANGELOG.md) at the repository root. The developer wiki is [`wiki/`](../wiki) and the user documentation is [`documentation/`](../documentation).

When two sources disagree, the product owner's latest decision wins, then the Build Bible, then the repository guides (`README.md`, `CONTRIBUTING.md`, `wiki/Contributing.md`, `wiki/UI-Standards.md`), then the conventions of the existing code.
