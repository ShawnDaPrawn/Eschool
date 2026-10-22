# Security

The threat model and the record of each phase's security checks (Build Bible sections 4 and 5.1).

| File | What it holds |
| --- | --- |
| [threat-model.md](threat-model.md) | Assets, actors, trust boundaries (panel, API, apps, worker, webhooks), top threats and their mitigations. Updated when a phase adds a surface. |
| [phase-0.md](phase-0.md) | The Phase 0 security checklist: each item, how it is proven, and its status, plus secret-scanning findings that need the product owner. |
| `phase-N.md` | The same for each later phase. |

**Before a phase closes**, the Test and Review roles run the security checks the Build Bible lists in section 5.1 and record the results in that phase's file here.

Report a vulnerability privately to the product owner; do not open a public issue.
