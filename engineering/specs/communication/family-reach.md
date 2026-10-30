# Family reach

Action Plan step 2.6. The school office's answer to "can we actually reach
every family?" at `/family-reach` ("Family reach", under Communication).

## What it counts

Current learners (an active Student membership today, account on). For each,
the guardians the school counts everywhere else (`GuardianService`): active
guardian links, and legacy father, mother and guardian fields that have no link
row (an ended link overrides the legacy field). A learner is:

| Status | When |
| --- | --- |
| Parent app | at least one active guardian account has a current parent-app sign-in (a token Sanctum still accepts: younger than `sanctum.expiration` and before its own `expires_at`) |
| Verified email only | otherwise, at least one active guardian has a verified, valid email address |
| Cannot be reached yet | guardians exist, but none has the app or a verified email, or their accounts are off |
| No guardian recorded | no guardian at all |

The page shows the totals and percentage reached, reach by homeroom, and the
learners to follow up with the reason. Guardian accounts are read in a few
queries for the whole school, not per learner.

## Rules

- Super Admin, or office staff (not a learner, parent or teacher) with
  `student-list`.
- Only the current school's learners and guardians; nothing is written.
- Device registrations (`user_devices`, step 0.3) will replace the app
  sign-in signal once that lands; the HQ aggregate across schools is SA.10.

## Acceptance tests (`Communication/FamilyReachTest`)

| Given | When | Then |
| --- | --- | --- |
| Three learners whose guardians have neither | Farid verifies his email; Gina signs in to the parent app; her token expires (by age, then by `expires_at`); then Gina's account is switched off | all unreachable; then Ben by app and Asha and Cara by email; Ben unreachable while the token is expired; then Ben unreachable |
| Asha's two legacy parents with ended links | reported | no guardian recorded |
| A teacher, a parent, a learner | they open it | 403; the office's page shows no other school |
