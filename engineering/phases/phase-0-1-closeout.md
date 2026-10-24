# Phase 0 and 1 closeout

Updated 24 October 2026. This is a remaining-work checklist, not a new phase or
an expansion of the agreed scope. Follow the [delivery register](outstanding-work.md)
for implementation, automated checks, staging, acceptance and release status.
Passing a code check does not substitute for a real operational or user check;
pending external evidence does not stop independent development.

Product-owner decision (24 October 2026): there are no legacy clients, so
legacy-storage retirement, compatibility writes for those clients and migration
of archived legacy profile records are not Phase 1 closeout requirements.
Existing data and public objects must not be deleted as a shortcut. Privacy
retention and access controls still apply to any records that exist.

## Phase 0

The repository contains the foundation implementation: unsafe seed-feature
removals, queued delivery, SES feedback, outbox/audit/cents, API contracts,
shared app/web foundations, redesigned panel navigation/year tools, deployment
and backup definitions, and automated checks. The remaining closeout work is:

- [ ] Reconcile the operational/security records against the actual deployed
  configuration, including secret storage, Firebase restrictions and the patched
  runtime. Repository configuration alone does not prove deployment.
- [ ] Provision or verify isolated staging within the additional US$10/month
  ceiling; do not reuse production data or authority to fit the budget.
- [ ] Exercise real mail authentication/feedback and an isolated restore using
  the corrected quarantine procedure.
- [ ] Record worker/scheduler observation and affected device checks.
- [ ] Record owner review and, when available, first-time-user tasks. Keep
  unavailable participants separate from implementation status.

No cloud credential bindings are available in the current managed environment.
No cloud resource, live message, production migration or release is implied.

## Phase 1 implementation

| Area | Current evidence | Work before implementation is complete |
| --- | --- | --- |
| School isolation and memberships | Scoped models, active memberships, teams, school switching and two-school route tests merged | Keep the route/role matrix current for each addition; rehearse existing-data migration |
| Learner identity | Lifelong numbers, dated enrolments and compatibility projection merged in #68 | Finish dated staff assignments and consumers that must answer historical dates |
| Guardian authority | Verified approval, invitation lifecycle and canonical child ownership merged in #67/#72/#74 | Rehearse existing-family transition; approval enforcement remains deliberately disabled until that rollout is safe |
| Handles | Global names, guardian review and app flow merged in #70 | Real delivery/device evidence |
| Configurable profiles | Protected schema/API merged in #75; panel/app/context/export merged in #76; private file fields merged in #78; bounded legacy dry-run/backfill and opt-in private file copies implemented | No further legacy-storage bridge or retirement is required for Phase 1; verify current profile flows and apply privacy controls to retained data |
| Modules | School/package switches, route/navigation/context effects and money read-only merged in #69 | Staging exercise |
| Notifications | Registry, rules/preferences, durable delivery and app/settings flows merged in #71 | Real email/push exercise |
| App context and selection | Shared school/child selection, additive context and refresh paths merged | Register each remaining backend capability and verify its deep links |
| New sign-in methods | Password/handle/cookie foundations exist | Add passkeys, verified-domain Google/Microsoft and single-use parent email sign-in; prove replay/domain protections |
| Pictures | Shared avatar assets and picker exist | Implement avatar/photo persistence and approval, keeping official learner photos separate |
| School information | Backend/editor and typed shared My school client merged in #77 after all 27 checks passed | Staging verification remains separate |
| Platform operations | Tenant/module foundations exist | Build platform-only school onboarding, packages, usage/health/delivery views and timed/reasoned/audited support access with protected-data exclusions |
| Imports | Legacy importers exist | Add guided mapping, validation report, dry run and rollback for all agreed data sets, including SA-SAMS and integer-cent opening balances |
| Privacy | Guardian consent and audit foundations exist | Complete general consent records, table retention policy/job, provider agreement templates and authorised person exports |
| Final verification | Feature tests and two-school route suite run continuously | Complete new route/role coverage and the combined onboarding/import/approval demo; verify supported clients |

## Order to finish

1. Completed: #76 merged after all 26 checks passed, including both Android
   suites and the full browser sweep.
2. School information merged in #77; private file implementation merged in #78.
   Finish pictures and verify current profile flows.
3. Complete modern sign-in and the platform school-onboarding path.
4. Complete imports and privacy tooling, plus dated staff/report consumers.
5. Run the combined school creation → import → guardian onboarding scenario,
   reconcile every requirement and record staging/acceptance evidence honestly.

Keep each feature reviewable and tested, merge completed work into main, and
return the open PR count to zero after each completed batch. Do not describe
Phase 1 as nearly finished while the implementation rows above remain open.
