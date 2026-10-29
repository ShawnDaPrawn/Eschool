# NavigateEd — instructions for Codex and other AI engineers

This repository is the WRTeam eSchool-derived NavigateEd monorepo. Do not
re-platform or rewrite it: grow the existing Laravel backend and Flutter apps.

## Primary product specification

The product owner's **NavigateEd Build Bible** (dated 21 October 2026,
71-page PDF) is the authoritative build plan, phase specification and
acceptance contract. It was supplied separately to the product owner and is
**not** included in this public repository. It contains information that
should not automatically be published. Before doing phase implementation
work, obtain the full document through the active task or a private workspace
copy and read the relevant sections. Never imply you have read it if it is
not actually accessible.

When the Build Bible is available, read in this order:
1. Sections 1–5: mission, seed, architecture, mandate and standards.
2. The applicable phase in sections 6–12, including tests and exit gate.
3. Appendices A and B for data models, module and notification catalogues.
4. Appendix E Build log for completed work, deferrals and open decisions.
5. `README.md`, `CONTRIBUTING.md`, `wiki/Contributing.md` and
   `wiki/UI-Standards.md` for repository-specific procedures.

Precedence: latest explicit product-owner decisions > Build Bible >
repository guides > legacy implementation conventions. If the Bible is
not available, limit work to safe repository maintenance or ask for the
document before claiming compliance with a phase or its acceptance gate.
Do not put the private Bible or secrets into public issues, PRs or commits.

## Architecture and compatibility

- `admin-panel/`: Laravel 12 / PHP 8.3 / MySQL backend, panel, public pages
  and API. Extend it rather than create a replacement backend.
- `e-school/`: existing Flutter learner and parent app.
- `e-school-teacher/`: existing Flutter teacher app and future flavours.
- `packages/eschool_core/`: shared app theme, API client and widgets.
- Keep v1 API routes and response fields functioning for installed apps.
  Introduce new capabilities through additive, contract-tested v2 APIs.
- Use services for business rules, policies for authorization, FormRequests
  for writes, events/outbox for cross-domain work, and queues for async work.
- Keep the existing Material 3 branding/theme and components; improve
  navigation and usability without replacing the established visual identity.
- Design multi-school features for strict tenant isolation, with roles and
  module availability scoped per school.

## Non-negotiable engineering checks

- Never leak data across schools. Test school isolation on every affected
  endpoint, including indirect access through IDs, files and notifications.
- A learner account may not be activated without verified guardian approval.
  Apply POPIA and safeguarding-by-design principles.
- Never introduce money floats for postings or balances. Use integer cents,
  double-entry ledger semantics, transactional writes and idempotency keys.
- Route model calls through the designated AI gateway, with moderation,
  metering and controls; never embed provider keys in clients.
- Avoid breaking existing app/API contracts, installed clients or imports.
- Every feature requires automated happy-path, wrong-role, wrong-school and
  wrong-owner tests, plus affected-profile self-testing.
- Do not treat production release as authorized merely because tests pass.
  Respect the Build Bible's staging acceptance and production approval gate.

## Product-owner delivery decisions (23 October 2026)

- Phase acceptance is a progress checkpoint, not a development freeze.
  Continue independent implementation, tests and safe merges while staging,
  observation periods, devices, testers or acceptance remain pending.
- Track implementation, automated verification, staging verification,
  acceptance and production release separately. Never report an unrun check
  as passed. Record outstanding checks with impact and the next action.
- Known data-loss, cross-school exposure, authentication or unusable-build
  risks block the affected change; they do not stop unrelated development.
- Run relevant tests for each change. An unrelated failing check needs a
  documented impact assessment, not a blanket exception or a frozen queue.
- Retain the recorded Phase 0/1 product defaults. Additional staging and
  email spend has a hard ceiling of US$10/month, excluding the existing
  production bill. Do not provision above it or weaken isolation to fit it.
- The owner reviews staging initially; first-time-user evidence remains
  pending until testers are available. Production still needs acceptance.

## Workflow for each phase

- Confirm the actual current repo state and completed phases: the Bible
  describes a seed and a target, not proof that any phase has shipped.
- Write a phase plan and feature specifications under `engineering/` using
  the repository's agreed naming conventions.
- Implement one reviewable feature per branch/PR, with its tests and docs.
- Follow `wiki/Contributing.md` for PR conventions. Require green CI.
- Maintain the API contract, documentation, relevant catalogues and Build
  log; record anything deferred and the reason.
- Self-test on the applicable client profiles and document evidence before
  claiming an exit gate has passed.

## Verification commands

- `cd admin-panel && php artisan test`
- `cd e-school && flutter analyze --no-fatal-infos && flutter test`
- `cd e-school-teacher && flutter analyze --no-fatal-infos && flutter test`
- `cd packages/eschool_core && flutter analyze --no-fatal-infos && flutter test`
- `cd documentation && npm ci && npm run build`

Also run any additional CI, security, browser and integration checks required
by the phase specification. Never assert that a check passed unless run.

## Semester retirement decision (23 October 2026)

The owner confirmed that no installed clients use semesters and authorized
replacing that implementation with school terms instead of maintaining semester
compatibility. Migrate stored IDs/references safely and update the bundled apps
and consumers together; do not create a duplicate semester projection or bridge.
This exception is specific to semesters, not authorization to break other APIs.

## Phase 2 pilot scope decision (24 October 2026)

The owner has cancelled the pilot stage and requested Phase 2 completion today.
Pilot onboarding/import tooling, a pilot champion feedback form/status page and
a full-term production parallel run are removed from Phase 2 completion scope.
Keep the existing marks reconciliation command available; do not remove data or
use the cancellation to claim unrun checks passed. Finish and safely merge the
retained academic, communication, export, language and desktop work. Continue
recording target SA-SAMS import qualification, fluent translation review, staging/
device/performance and independent security evidence separately. The cancellation
is not production release authorization or a spending-budget change.

## Concurrent Phase 3 and Phase 4 decision (24 October 2026)

The owner explicitly authorized two agents to start these phases concurrently,
as the Build Bible schedules them. Use isolated worktrees and small feature PRs;
the lead coordinates shared contracts and integrations. Follow
`engineering/phases/phase-3-4-coordination.md` for ownership and dependencies.
Missing AI/mastery/awards contracts block their consumers, not unrelated work.
Native flavours never grant server roles or permission to reuse teacher access.
Preserve Phase 2 transcription and academic behavior. Continue to separate tested
implementation from external acceptance; production, stores and paid-provider
activation are not authorized merely by this development decision.

## Phase 5 gateway decision (26 October 2026)

The owner authorized Phase 5 implementation and has no payment gateways lined up.
Peach is not a preferred provider. Keep payment boundaries provider-neutral and
deprioritize provider-specific adapters/onboarding; continue billing, wallets and
commerce without waiting for a gateway. This supersedes the Phase 0 Peach default.
No live payment/provider activation or extra spend is implied.

## Phase 5 increments 1–15 and legacy money decision (27 October 2026)

The owner authorized implementation, testing and safe main integration of all
increments1–15 in `engineering/phases/phase-5-delivery-1-15.md`. Payment gateway
integration remains deferred. Old legacy money records may be discarded rather
than imported/reconciled into the new ledger; retire their dependencies safely.
This does not authorize deleting the new immutable ledger/domain history or
production activation. Configure school-specific financial rules explicitly and
keep unrelated development moving while particular evidence/settings are pending.

## Whole-app consolidation decision (29 October 2026)

The owner accepted the whole-app review fixes and UX/UI standardization pass,
without starting Phase6. Remove fully replaceable legacy implementations after
moving their callers and required dependencies to the new build. Do not retain
parallel writable money systems or invest in polishing replaced legacy screens.
Preserve new immutable history and distinct capabilities without complete
replacements. Follow engineering/phases/whole-app-consolidation.md. Gateway
integration and production activation remain outside this authorization.

## Consolidation deployment decision (29 October2026)

The owner explicitly requested finishing the accepted fixes and safely deploying
the updates. This authorizes the existing-environment rollout after source
qualification, merge, backup and applicable deployment checks; retain the
repository's protected production approval process. Record the running release
and post-deployment verification separately from main integration. Do not infer
new infrastructure spend, payment-gateway activation, native-store credentials
or Phase6 work from this authorization.
