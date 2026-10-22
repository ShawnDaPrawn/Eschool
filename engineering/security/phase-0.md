# Phase 0 security record

The Build Bible's Phase 0 security checklist (section 6, "Security checks"), how each item is proven, and its status. A phase closes only when every row is ticked (Appendix D, "Phase exit"). Each work stream fills in the rows it owns; the Test and Review roles confirm them at the gate.

Status values: **Proven** (evidence linked), **In place, not yet run** (built, waiting for an environment), **Open** (owned by another stream, not yet delivered here).

## Checklist

| # | Item | How it is proven | Owner | Status |
| --- | --- | --- | --- | --- |
| 1 | System Update and the GET maintenance routes are gone; a Playwright test asserts 404 on their old URLs | Removal (feature 0.2) plus a Playwright check under `admin-panel/e2e/` | S1, S3 | Open |
| 2 | Password policy enforced on every password endpoint; a test tries a weak and a breached password | Feature tests per password endpoint | S6 | Open |
| 3 | Inactive teacher cannot sign in or call any teacher route; previous tokens die at login | Feature tests on `EnsureTeacherActive` and token revocation | S6 | Open |
| 4 | CORS answers only the portal and panel origins | Feature test on `config/cors.php` origins | S6 | Open |
| 5 | Uploads with a double extension, a wrong MIME or a traversal name are refused; stored names come from `sanitizeFileName` | Upload feature tests | S6 | Open |
| 6a | Secrets: `gitleaks` passes on the whole history | `gitleaks` 8.30.1 over every commit on every branch and tag (`--log-opts=--all`) with `.gitleaks.toml`; runs in CI on every pull request, push to `main` and weekly (`.github/workflows/security.yml`). Run here on 2026-10-21 over 169 commits: 0 findings after allowlisting the two proven false positives below | S4 | **Proven** |
| 6b | No key remains in `settings` rows after the move to Secret Manager | Test that the secret-bearing `settings` keys are gone or empty; secrets created in Secret Manager | S6 (secret names added to `infra/gcp/provision.sh`) | Open |
| 7a | Dependency audits pass | CI `security-audit` job: `composer audit --locked` (fails on any advisory), `npm audit --audit-level=critical` for the docs, `dart pub outdated` (informational) for both apps and every package. Run here on 2026-10-21: Composer "No security vulnerability advisories found"; npm no critical advisories (lower-severity advisories exist in the Docusaurus build tooling, which never ships to users); `dart pub outdated` on `eschool_core`: direct dependencies up to date | S4 | **Proven** (on this branch's lock files; S1's dependency removals re-run in CI on merge) |
| 7b | Dependabot is open on all four projects | `.github/dependabot.yml`: Composer (`admin-panel`), pub (`e-school`, `e-school-teacher`, `packages/*`), npm (`documentation`, `admin-panel/e2e`), Docker (`admin-panel`), GitHub Actions; validated against the SchemaStore schema. Takes effect once merged to `main`; security updates also need **Settings > Code security > Dependabot security updates** switched on by the product owner | S4 | In place; open until merged and the first update PRs appear |
| 8 | CSP report-only shows no violations on the panel's main pages | `SecurityHeaders` CSP in report-only mode; Playwright run collecting reports | S6, S3 | Open |
| 9 | Backups: a restore of yesterday's production backup to staging completes and the panel signs in | Restore drill in [`runbooks/restore.md`](../runbooks/restore.md); production backups and point-in-time recovery set by `infra/gcp/provision.sh` | S4 | In place, **not yet run** (needs staging and production provisioned; record the drill in restore.md) |

## Also delivered in Phase 0 (Bible 5.1 and 5.6)

| Rule | Evidence | Status |
| --- | --- | --- |
| Logs carry no personal data beyond ids | `LOG_CHANNEL=json` (`App\Logging\JsonFormatter`): context keeps only numbers, booleans, id values and a few descriptive strings; e-mail addresses, SA ID numbers and phone numbers in messages are masked; no request body is ever added. `tests/Feature/StructuredLoggingTest.php` (8 tests) | **Proven** |
| Exception traces carry no arguments or bound values | `zend.exception_ignore_args=On` in `admin-panel/docker/php.ini`; the formatter builds traces without arguments and keeps a query's SQL placeholders only | **Proven** (formatter test); image setting not yet run on Cloud Run |
| Secret Manager only; no keys in CI | Workload Identity Federation for GitHub Actions; the deployer can be impersonated only by jobs in its GitHub environment, and production's requires a reviewer; secrets referenced by name only (`infra/gcp/env/*.env` hold no values) | In place, not yet run |
| Least privilege in the cloud | One service account per role; the deployer cannot read secrets or data; backups through a four-permission custom role ([environments.md](../runbooks/environments.md), "Permissions") | In place, not yet run |
| The worker is not reachable from the internet | `navigateed-worker` ingress `internal`; health judged from outside through `GET /up/worker` | In place, not yet run |

## Secret-scanning findings

The scan of the whole history on 2026-10-21 (gitleaks 8.30.1, default rules) reported four findings. None is a real credential; no history rewrite is needed and nothing needs rotating.

| File | Commit | Kind | Verdict | Action |
| --- | --- | --- | --- | --- |
| `e-school/android/app/google-services.json` (lines 18, 37) | 1103e37e99 | `gcp-api-key` and `generic-api-key` on `api_key.current_key` | False positive: Firebase Android API key, a public client identifier shipped in every APK | Allowlisted in `.gitleaks.toml` for this file, these rules and the Firebase key format only |
| `e-school-teacher/android/app/google-services.json` (lines 18, 37) | 1103e37e99 | the same | the same | the same |

**Check: needs product owner.** Firebase API keys are safe to publish only while they are restricted. In the Cloud Console (project `navigateed-d8c06`, APIs & Services > Credentials), confirm each Android key is restricted to the apps' package names (`tech.tokensoft.navigateed`, `tech.tokensoft.navigateed.teacher`) with their signing certificate fingerprints, and to the Firebase APIs the apps use; the iOS and web keys likewise to their bundle ids and portal hostnames. This cannot be checked from the repository.

A further search of every commit's changes for private keys, service-account JSON (`private_key_id`), live Stripe, Razorpay and Flutterwave keys, Stripe webhook secrets and AWS access keys found only a dummy test value (`FLWSECK-` placeholder in `PaymentWebhookTest`).

**Rotate: needs product owner.** Nothing.

## See also

- [threat-model.md](threat-model.md)
- [Spec: environments and operations](../specs/platform/environments.md)
