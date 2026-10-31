# Who can see what

Action Plan step 1.15. A read-only page under the school's settings
("Who can see what", `/access-review`) that answers "what can this person, or
this role, reach?" in the same terms as the role editor
([`role-editor.md`](role-editor.md)).

## What it shows

For a **person** (current staff of the school) or a **role** of the school:

- the level (No access, View, Contribute, Manage) in each module of
  `config/module_access.php`, with modules the school has switched off marked;
- the abilities held, sensitive ones marked;
- any permissions no level or ability explains;
- the panel pages in their menu. For a person this is exactly their own
  sidebar (`Navigation::visible`), appointments, allocations and caseloads
  included ("view as this person"). For a role it is worked out from the
  role's permissions and the module switches, and pages that also need an
  appointment are marked "depends on appointment".

## Rules

- Super Admin, or staff (not a learner or parent) with `role-list`, checked
  fresh on each request.
- People and roles resolve inside the current school; another school's person
  (no membership here) or role is not found. The person list holds current staff
  only: no learners or parents.
- Nothing is written.

## Acceptance tests (`Platform/AccessReviewTest`)

| Given | When | Then |
| --- | --- | --- |
| A custom role with People at Contribute, the sensitive profile ability and `role-list` | reviewed | People Contribute, Academics none, the sensitive ability marked, `role-list` listed on its own, the review page in its menu and not the roles page |
| A teacher | reviewed as a person | role Teacher; pages identical to their own sidebar; Fees none |
| A teacher, a parent, a learner; another school's person or role | they open it, or are looked up | 403; not found; parents and other schools' staff are not offered |
| The page | a role and a person are reviewed | the module table and the pages |

Browser: `e2e/tests/navigation.spec.ts` at 1024 and 1280 pixels; teachers
refused.
