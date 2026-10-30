# Feature flags and staged rollout

Action Plan SA.14. Owner role: Backend and Admin UX (HQ).

HQ lets a new feature reach chosen pilot schools first, then everyone, and can switch it off at once if it misbehaves. The apps already read `feature_flags` from `/api/v2/me/context` (a map of key to boolean); platform flags add keys to that map, or narrow existing ones, without changing its shape.

## User stories

- As Navigate Ed operations, I create a flag for a feature that is about to ship. It starts off everywhere.
- I put it in beta and switch it on for two pilot schools by name; every other school still sees it off.
- When the pilot goes well, I turn it on for everyone, and can still keep one school off by name.
- If it breaks, I kill it with a reason, and it is off for every school at once, whatever the school switches say.
- As a school, I never see or change HQ's flags; my apps just receive the result for my school.

## Rules

- **Value at a school.** Killed → off. Rollout `off` → off (school switches are ignored). Otherwise a school switched by name gets its switch (on or off). Otherwise `everyone` → on, `beta` → off.
- **Built-in flags.** The context already carries flags the code computes (for example `academic_content`, `teaching_assistant`). A platform flag with the same key can only narrow it: the app receives the built-in value and the platform value together, so HQ can switch a shipped feature off for a school but never on where the code does not offer it. Platform-only keys are added after the built-in ones. No key is ever removed.
- **Keys.** Lower case, a letter first, then letters, digits or underscores, 3 to 64 characters, unique. A flag is never deleted. Retire it by leaving it off, or on for everyone, and removing it from the app's code.
- **Kill switch.** Needs a reason, which is shown on the page and recorded in the audit log.
- **Concurrent edits.** The flag form carries a revision. A school switch also raises it, so a form opened before is refused (409, reload).
- **Who.** `flags.manage` (Owner, Operations) reaches the page and every form. Support, Finance, Content and Sales get no link and 403. School accounts never reach `/saas`.
- **Audit.** `platform_feature_flag_created`, `platform_feature_flag_changed`, `platform_feature_flag_killed`, and `platform_feature_flag_school_switched` (against the school).
- **Server code.** `FeatureFlags::enabled($key, $schoolId)` answers for one platform flag at one school. An unknown flag is off.

## Data

| Table | Tenancy | Holds |
| --- | --- | --- |
| `platform_feature_flags` | global | key, description, rollout (`off`, `beta`, `everyone`), kill switch and reason, revision |
| `platform_feature_flag_schools` | required school-owned (foreign key to the school) | one row per school switched by name: on or off |

The migration's `down` refuses to drop tables that hold rows.

## Acceptance tests (`Platform/HqFeatureFlagsTest`)

| Given | When | Then |
| --- | --- | --- |
| Operations; Pieter teaches at School 1 and runs School 2 | create `parent_home`; beta with School 2 on; everyone with School 2 off; clear School 2; kill it with School 1 switched on | off at both; on at 2 only; on at 1 only; on at both; off at both (a kill without a reason is refused); each change audited, school switches against their school |
| The built-in `academic_content` (on) and `teaching_assistant` (off for this teacher) | platform flags of the same keys rolled out to everyone; `academic_content` switched off at School 1 | `academic_content` off at School 1, still on at School 2; `teaching_assistant` stays off; the same keys as before |
| The value rule | every combination of rollout, kill switch and school switch | as in the rules; an unknown flag is off |
| Owner | a bad key, a duplicate key, a stale form, an unknown school or flag | refused (validation, 409, 404); nothing written |
| Support, Finance, Content, Sales; a school Super Admin | every page and form | no link; 403; nothing written |

## Endpoints (HQ panel)

| Method | Path | Name |
| --- | --- | --- |
| GET | `/saas/flags` | `saas.flags.index` |
| POST | `/saas/flags` | `saas.flags.store` |
| PUT | `/saas/flags/{flag}` | `saas.flags.update` |
| POST | `/saas/flags/{flag}/schools` | `saas.flags.schools` |

## Not in this step

- A percentage rollout (a share of schools or users). Beta by named school is what the pilot needs.
- Per-user flags.
- Flags in the web panel's own views. Server code can call `FeatureFlags::enabled`; no panel feature uses it yet.
- Updating the OpenAPI description of `feature_flags` ("empty until Phase 1"). The shape is unchanged, and the description waits for the next client regeneration.
