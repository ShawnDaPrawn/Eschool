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
- **Modules the school has switched off are hidden** and keep whatever the
  role already holds in them, so switching a module off and editing a role does
  not silently remove access the school will want back.
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
| A checkbox-editor role with two People permissions | edited with and without "Keep them" | listed as individual permissions; kept; then dropped |
| Teacher or Super Admin; a built-in name; an unknown module; another school's role; a teacher user | they edit or create | 403, validation errors, not found, 403; nothing changed |
| The panel | create, open, rename and raise a level | saved and redirected; the roles list offers "Access by module" |

Browser: `e2e/tests/role-editor.spec.ts` (accessibility and fit at 1024 and
1280 pixels; teachers refused).

## Not yet

Role templates (1.14) and the "who can see what" review (1.15).
