# Module access levels

Action Plan step 1.12 (foundation for the new role editor, 1.13, and role templates, 1.14).

## Why

A school administrator gives staff access by job ("our bursar", "the sports coordinator"), but the role editor lists about 200 permission names, many inherited from the original eSchool product. Access must be described by module: which modules a role can use, and how much it can do in each.

## The model

- **Modules** are the existing registry (`config/modules.php`).
- **Levels per module:** `none`, `view`, `contribute`, `manage`. Levels are cumulative.
- **Abilities:** named special permissions granted one by one, never implied by a level (for example releasing marks, approving bills, seeing health information).
- **Sensitive abilities** open records about children's health, wellbeing or identity, or approve money. The role editor shows them apart, and granting one is audited.
- `config/module_access.php` maps each level and ability to the **existing permission names**. Nothing is renamed; policies and middleware keep checking the same names. This is a translation layer, not a rewrite.

## Rules

- Every permission the Super Admin role holds (the set a custom role may be given) is mapped exactly once, or listed as retired. `ModuleAccessTest` fails otherwise, so a new permission cannot be added without placing it.
- Retired permissions (`fees-type`, `fees-classes`, `fees-paid`, `fees-config`) are never offered.
- Teacher-only permissions (lessons, topics, assignments, attendance capture, online exams) stay with the Teacher system role and are not assignable to custom roles, as today.
- A role's level is read back from its permissions: the highest level whose permissions it fully holds.
- Platform staff and impersonation never gain school access through this map; clinical and counselling access still needs its existing explicit grants.

## Service

`App\Services\Platform\Modules\ModuleAccess`: `modules()`, `permissionsFor(module, level)`, `levelOf(module, held)`, `abilities(module)`, `permissionsForChoices(levels, abilities)`, `owners()`.

## Acceptance tests (`Unit/ModuleAccessTest`)

| Given | Then |
| --- | --- |
| The Super Admin permission set | each permission maps to exactly one module (or is retired); none unknown; no retired one offered |
| The access map | every module in it is registered |
| A module's levels | cumulative; abilities never part of a level |
| A role's permissions | read back as the highest fully held level |
| Chosen levels and abilities | turned into permission names; unknown ability refused |
| Health, wellbeing, identity, roles, billing, marks release | marked sensitive |

## Next

1. The role editor (1.13): modules down the side, levels across, abilities beneath, modules switched off hidden through `ModuleGate`.
2. Role templates (1.14) defined as module levels and abilities.
3. "Who can see what" review (1.15).
