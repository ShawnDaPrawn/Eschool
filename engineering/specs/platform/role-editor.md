# Role editor by module

Action Plan step 1.13, on the module access map (1.12,
[`module-access.md`](module-access.md)). A school administrator describes a
custom role as a level per module and named abilities, instead of ticking some
of 200 permission names.

## Screen

`/roles-by-module/new` and `/roles-by-module/{role}` (linked from the roles
page: "Create a role by module", and an "Access by module" button per custom
role). Modules down the side, the levels No access, View, Contribute and Manage
across (each includes the lower ones), the module's ordinary abilities under
its row, and the sensitive abilities in their own section. The checkbox editor
stays available for now.

## Rules (`RoleEditor`)

- **Writes the mapped permissions.** The chosen levels and abilities become
  `ModuleAccess::permissionsForChoices`, limited to what a custom role may hold
  (the Super Admin set less retired permissions). Policies keep checking the
  same permission names.
- **Modules the school has switched off are hidden** and keep every
  permission the role already holds in them (even a partial grant no level
  explains, and whether or not "Keep them" is ticked), so switching a module off
  and editing a role does not silently remove access the school will want back.
- **A failed save shows what was sent.** After a validation error the form
  re-ticks only the abilities (and "Keep them") that were submitted; an
  ability group with nothing ticked counts as empty, not as the held abilities.
- **Permissions no level explains** (from the checkbox editor, such as two of
  the six People view permissions) are listed and kept unless the
  administrator unticks "Keep them".
- **Sensitive abilities** (sensitive profile details, roles, releasing marks,
  fee accounts, billing, payments and credits, wallet finance and the others
  marked `sensitive` in `config/module_access.php`) need the confirmation box;
  each grant and removal is audited (`role_sensitive_ability_granted`,
  `role_sensitive_ability_removed`) as well as the whole change
  (`role_access_saved`, permissions before and after).
- **Only custom roles.** Super Admin, Teacher, Parent and Student cannot be
  edited or reused as names; names are unique per school; a role id from
  another school is not found; `role-create` and `role-edit` are required.

## Acceptance tests (`Platform/RoleEditorTest`)

| Given | When | Then |
| --- | --- | --- |
| No role | People at Contribute and Communication at View are saved | exactly the mapped permissions; the editor reads the levels back; audited; lowering People to View removes the higher grants |
| A sensitive ability | saved without, then with, the confirmation; then removed | refused and nothing saved; granted and audited; removed and audited |
| A role with Groups at Manage | Groups is switched off and the role edited | Groups permissions kept |
| A role holding only `cards-manage` (Cards off) | edited with "Keep them" unticked | `cards-manage` kept |
| A role holding an ordinary and a sensitive ability | saved with both unticked under a taken name | refused; the form shows both unticked |
| A checkbox-editor role with two People permissions | edited with and without "Keep them" | listed as individual permissions; kept; then dropped |
| Teacher or Super Admin; a built-in name; an unknown module; another school's role; a teacher user | they edit or create | 403, validation errors, not found, 403; nothing changed |
| The panel | create, open, rename and raise a level | saved and redirected; the roles list offers "Access by module" |

Browser: `e2e/tests/modules.spec.ts` (role editor accessibility and fit at 1024 and
1280 pixels; teachers refused).

## Templates (Action Plan 1.14)

`config/role_templates.php` ships starting points for common school jobs:
Principal, Deputy principal, School office, Bursar or finance officer, Head of
department, Grade head, Sports and activities coordinator, Reception and gate,
IT administrator. "Start from a template" on the new-role page ticks the
template's levels and abilities and suggests its name; nothing is saved until
the administrator saves. Modules the school has switched off are not granted.
The system roles (Super Admin, Teacher, Parent, Student, Counsellor,
Safeguarding) are left as they are, so Teacher and Counsellor are not
templates. **Sensitive abilities are never part of a template**
(`Unit/RoleTemplatesTest` enforces it, and that every template uses known
modules, levels and abilities within what a custom role may hold): marks
release, finance approvals, sensitive profiles, health records and roles are
ticked on purpose. A librarian template waits for the library module.

## Not yet

The "who can see what" review (1.15) and choosing a template while inviting
staff.
