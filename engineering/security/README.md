# Security

The threat model and the record of each phase's security checks (Build Bible section 4, "Where engineering artefacts live", and section 5.1).

| File | What it holds |
| --- | --- |
| [threat-model.md](threat-model.md) | Assets, actors, trust boundaries (panel, API, apps, worker, webhooks), top threats and their mitigations. Updated when a phase adds a surface. |
| [phase-0.md](phase-0.md) | The Phase 0 security checklist: each item, how it is proven, and its status, plus secret-scanning findings that need the product owner. |
| `phase-N.md` | The same for each later phase. |

From Phase 6 this folder also holds the processing register and the retention table.

**Security gate, every phase** (Bible 5.1): before a phase closes, the Test and Review roles run and record here the cross-school suite, the authorisation matrix, the dependency audits, secret scanning on the repository, the upload tests, the rate-limit tests, and a review of every new table against the retention and encryption rules. An outside firm tests before the pilot (end of Phase 2) and before money features launch (end of Phase 5).

Report a vulnerability privately to the product owner; do not open a public issue.
