# Decision records

Architecture and product decisions, one file each, numbered in the order they were taken: `NNNN-<title>.md`. Each record has four parts: **context** (what forced the decision), **options** (what was considered), **decision** (what we chose and the details that matter), **consequences** (what follows, good and bad, and what is left open).

A record is never rewritten after it is accepted. When a decision changes, add a new record that supersedes the old one and set the old one's status to "Superseded by NNNN".

| # | Decision | Status |
| --- | --- | --- |
| [0001](0001-seed-kernel-and-domains.md) | Grow the seed: one Laravel app, a platform kernel underneath, domains beside it | Accepted |
| [0002](0002-api-v2-and-openapi.md) | API v2 under `/api/v2` with `openapi.yaml` as the contract; v1 frozen, add-only | Accepted |
| [0003](0003-app-packages-and-web.md) | Thin app shells over shared packages, web builds on Firebase Hosting, portal hostnames | Accepted (hostnames defaulted, Q0.2) |
| [0004](0004-transactional-email.md) | Transactional email through Amazon SES in `af-south-1`, bounces and complaints through SNS | Accepted (defaulted, Q0.1) |
| [0005](0005-student-number-and-handles.md) | Lifelong student number and learner handle rules | Accepted (defaulted, Q0.5 and Q0.6); built in Phase 1 |

Defaults taken while the product owner has not answered are listed in [`phases/phase-N-questions.md`](../phases/phase-0-questions.md); when the answer differs from the default, a superseding record follows.
