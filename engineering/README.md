# Engineering

Working files for building NavigateEd on top of the seed codebase. The NavigateEd Build Bible (held by the product owner) is the source of truth; this folder holds what the Bible asks engineers to keep in the repository.

| Artefact | Path |
| --- | --- |
| Phase plans and open questions | `phases/phase-N.md`, `phases/phase-N-questions.md` |
| Feature specs | `specs/<module>/<feature>.md` |
| Decision records | `adr/NNNN-<title>.md` (context, options, decision, consequences) |
| Self-test runs | `self-tests/phase-N.md`, screenshots in `self-tests/evidence/` |
| Runbooks (deploy, rollback, restore, incident, release) | `runbooks/` |
| Threat model and security test results | `security/` |
| Living appendices of the Bible (data model, module and notification catalogues, build log) | `bible/` |

The changelog is [`CHANGELOG.md`](../CHANGELOG.md) at the repository root, newest first. The developer wiki is [`wiki/`](../wiki) and the user documentation is [`documentation/`](../documentation).

Precedence when sources disagree: the product owner's decisions, then the Bible, then the repository guides (`README.md`, `CONTRIBUTING.md`, `wiki/Contributing.md`, `wiki/UI-Standards.md`), then existing code conventions.
