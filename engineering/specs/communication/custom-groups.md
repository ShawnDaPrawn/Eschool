# Custom groups: smart groups, private or shared

Action Plan 2.5. Owner role: Backend (communication). Builds on the [communication centre](centre.md).

Staff could already save fixed lists of people ("saved custom audiences") in the panel and the apps. This adds smart groups, which keep rules instead of names, and lets the author choose whether a saved audience is private or shared. Scope rules are unchanged: nobody can reach a person through a saved audience whom they could not address directly (`CommunicationRecipients`, `CommunicationRules`, `MessagePolicy::scope`).

## Rules

- **Two kinds.** A *fixed list* keeps the people chosen (as before). A *smart group* keeps up to ten rules, each a grade, homeroom, teaching group or activity group of one school year, plus whether it reaches *any* of the rules' learners or only those in *all* of them, and whether it reaches the learners or their parents and guardians. The whole school is not a rule. An audience's kind is fixed once saved.
- **Resolved at preview and at sending.** A message to a smart group stores the rules, not the people. Its recipients are worked out when the message is previewed and again when it is published, so a learner who joins a rule's group in between is included, and one who leaves is not. Recipients are frozen at publication, as for every message.
- **Authority.** Saving a smart group needs authority to address every rule (the check a message to that scope gets). Sending to one rechecks every rule for the author, and every rule for the reviewer, at submission and at publication. A lost authority blocks the message at sending (`authority_changed`).
- **Private or shared.** A private audience is offered only to its creator (and to communication managers, who see every saved audience to manage them). A shared one is offered to other staff only when they may address everyone on it (a fixed list) or every rule (a smart group). New audiences from the panel start private. Lists saved before this change, and audiences written by an app that does not say, are shared, as they behaved before.
- **Preview.** The panel shows how many people a smart group reaches today. Each message's preview shows its recipient count before it is submitted.
- **Schools.** Audiences and their rules belong to one school. Another school's audience ids and group ids are not found.

## Interfaces

- Panel: the "Smart groups" section of the communication centre (`POST /communication-centre/audiences` with `kind=smart`, rules as `rule_selectors[]` of `kind:id`).
- API v2: `POST /api/v2/communication/audiences` and `PUT …/audiences/{audience}` take `kind`, `shared`, and for smart groups `match`, `parents` and `rules`. `GET …/audiences` and the configuration list each audience's `kind`, `shared` and `smart` rules. A message's `audience` snapshot carries `smart` for a smart group. See `openapi.yaml` (`CommunicationAudienceWrite`, `CommunicationAudience`, `CommunicationSmartRules`).
- Data: `audiences.kind` (`fixed`, `smart`) and `audiences.shared`. Definitions stay encrypted.

## Acceptance tests (`Communication/SmartGroupsTest`)

| Given | When | Then |
| --- | --- | --- |
| Tara's smart group of her teaching group's learners | she messages it, and Cara joins the group before it is sent | the preview counts 2; the message reaches Asha, Ben and Cara |
| A smart group of guardians; smart groups matching all or any of two rules | sent or counted | the guardians receive it; all = the learners in both; any = the learners in either |
| A parent, Theo (no allocation), a rule Tara may not address | they save a smart group | 403 |
| Tara's private group; then a shared one | Theo lists or messages it | not listed, 404; shared: not listed, 403; a manager sees it |
| Tara's authority ends after approval | the message is processed | blocked, no recipients |
| School B | lists audiences or opens Tara's | not found; a rule naming another school's homeroom 404 |
| A smart group | saved as a fixed list; 11 rules; rules on a fixed list; a whole-school rule | 422 |
| The app and the panel | create, list and use smart groups; an old-style fixed list | the contract holds; the old list stays fixed and shared; the panel shows the reach today; Theo 404 |

## Not in this step

- Exclusion rules ("everyone in Grade 7 except …") and rules on other attributes (houses, transport, fees).
- Smart groups that span school years.
- The apps' screens for smart groups (the API is ready).

An existing smart audience can be archived after its last scope becomes stale.
The panel may submit no selected rules for this recovery; the API retains its
nonempty rule schema and can submit the saved stale rules. Archival retains the
saved definition, while new audiences, changed definitions and reactivation
still require current scope authority. Archived audiences cannot send messages.
