# Academic competitions and debating between schools

Action Plan step 5.3. Owner roles: Backend, Admin UX, AI and Test. Built on [inter-school fixtures](inter-school-fixtures.md) (5.2) and [records shared between schools](../platform/shared-records.md) (5.1); AI feedback goes through the [AI gateway](../ai/gateway-foundation.md) with the [quarantine contract](../ai/quarantine-contract.md), the [safeguarding phrase guard](../ai/safeguarding-phrases.md) and an [evaluation corpus](../ai/evaluation-corpora.md); awards use [houses and awards](../community/houses-and-awards.md). Module `groups`; AI feedback also needs module `reasoning_feedback`.

## User stories

- **Activity manager (host):** I arrange a debate, a quiz or olympiad, or a speech contest with another school on NavigateEd (or one outside it, typed by name). I set the motion or topic, the format, the number of rounds and, for a debate, which side we take.
- **Activity manager (guest):** I answer the invitation and keep my entrants exactly as for a fixture. I see the motion, the host's name and, once we both accept, its team name. I never see its learners.
- **Activity manager (either school):** I name my school's adjudicators: our own staff, or people from outside by name. The other school never sees them.
- **Adjudicator (our staff, named by the manager):** I keep our adjudicators' result as a draft (scores, the winner, each of our entrants' score and place, and our written reasons) and submit it when we agree. The result counts once both schools submit the same scores and winner.
- **Adjudicator:** I paste a speech transcript or written argument and ask for AI feedback on its reasoning: structure, use of evidence, rebuttal and logical fallacies. It is advice for me only. It never decides or changes the result and nobody else sees it. If I want, I write feedback in my own words for one of our entrants; I am responsible for what I share.
- **Activity manager:** I present one of our school's own awards to our entrants for taking part, or for a placing once the result is confirmed.
- **Guardian (or the learner):** I see the competitions my child took part in once both schools accepted, the team's confirmed result (won, lost or a draw, and the team scores), feedback an adjudicator chose to share with my child, and awards linked to it. I never see my child's place or score, or any other child.

## What an academic competition is

A shared fixture (`shared_fixtures`) whose `kind` is an academic kind. The kinds live in `config/shared_records.php`:

| Kind | Entrants | Draw allowed | AI feedback on reasoning |
| --- | --- | --- | --- |
| `debate` | a team | no (the adjudicators name a winner) | yes |
| `quiz` (quiz or olympiad) | a team | yes (points decide) | no |
| `speech` (speech contest) | individuals | yes (points decide) | yes |

A new kind is one entry in `academic_kinds` and `competition_kinds`. Competitions and leagues (`shared_competitions`) may now be of these kinds too; a league table counts the adjudicators' winner instead of comparing scores. A fixture can only join a competition of its own kind.

Everything the fixture already does is reused unchanged: the invitation and its answer, each school's side (its activity group and team name), the team sheet (its entrants: learners in the group on the day, with a label and position, shared only by choice), rescheduling by the host and the rule that each school sees only its own roster.

## Data

`2026_11_12_530000_create_academic_competitions`:

- `shared_fixtures` gains `motion`, `format`, `rounds`, `host_stance` (proposition or opposition) and `winner` (the winning side both schools confirmed). These are facts every accepted school may see. `shared_fixture_sides` gains `reported_winner`, published like the score report.
- School-owned (`SchoolOwnedTables::REQUIRED`, `BelongsToSchool`):
  - `academic_adjudicators`: the school's own adjudicators (a staff user, or an outside name).
  - `academic_results`: the school's adjudicators' result, `draft` or `submitted`, with its entrants' scores and places and the reasons (both encrypted).
  - `academic_reasoning_reviews`: one request for AI feedback: who asked, the source (speech or written), whose entrant (ours or theirs), the stance, the state (`pending`, `completed`, `unavailable`, `quarantined`), the redacted text (encrypted, cleared once answered), the feedback (encrypted) and, if the adjudicator shared their own words, the learner, text and time.
  - `academic_award_links`: the award presentation made for one entrant, for `participation` or `placing`; one per learner, competition and basis.
- `down()` refuses while any of these holds rows or an academic competition exists.

## Rules

- **Who may do what.** Managers (`activity-groups-manage`) arrange, answer, change the details (host only, until a result is reported), name adjudicators and present awards. Named adjudicators and managers record the result and ask for AI feedback. The team's coach keeps the team sheet (fixture rules) but cannot adjudicate (403). Other staff get "not found"; parents and learners never reach staff reads (403).
- **What each school sees.** The fixture's own view, plus the motion, format, rounds and stances. Its own adjudicators, result and reviews only. While the two submitted results differ, each sees the winner the other submitted, so they can sort it out (as for fixture scores).
- **Results.** Only a person records a result. A draft is never seen by the other school and reports nothing. Submitting is allowed from the day of the competition once both schools accepted; it becomes the school's report (`reported_host_score`, `reported_guest_score`, `reported_winner`), and both reports agreeing confirms the shared result (`Fixtures::settle`, the same locking read after the fixture lock). Against a school outside NavigateEd the host's submission confirms it. In a quiz or speech contest the winner must follow the scores; in a debate the adjudicators name it and there is no draw. A school records places and scores for its own entrants only. The fixture score endpoint refuses academic kinds (409).
- **Families** are told inside their own school that a competition result is ready (the fixture notice, titled "Competition result"). The family read shows no place, score or ranking of a child.

## AI feedback on reasoning (gateway feature `reasoning_feedback`)

- **Advice only.** The output is shown to the adjudicator who asked and to the school's activity managers. It is never written to a result, never carries a score and never reaches a learner or family. Only text the adjudicator writes and shares with one of their own school's entrants (from a review of their own side) is shown to that family.
- **Off by default, twice.** The platform switch (`ai.inference.enabled` and the feature's `enabled`, both false) and the school switch (module `reasoning_feedback`, default off, in the Activities product; HQ can switch it at `/saas/ai` through `ai.inference.school_modules`). While either is off, nothing is stored, redacted or sent (409 or 403).
- **Budget.** The school, person and learner daily token and cent caps in `config/ai.php` (school 300,000 tokens and 200 cents a day; a person 60,000 tokens and 50 cents), which HQ and the school's own `/ai-settings` can lower as for every school feature. The scope has no learner, so no learner id is stored or charged.
- **Redaction before storing or sending.** `ReasoningReviews` removes both schools' names, an outside opponent's name, both team names, this school's entrants' names and labels, the other school's shared sheet labels, this school's adjudicators, the asking adult, emails and long numbers (`[name]`, `TutorContext::minimize`). Ordinary words in school names (for example "High" or "School") are kept. The model receives only `{task, kind, format, motion, stance, source, text}`.
- **Policy** (`ReasoningFeedbackPolicy`): the only input is one pending review in this school, asked for by the adjudicator calling, who still judges this competition with the module on; reauthorized before every step. Audience staff; roles Teacher, Super Admin, School Admin, Principal, Grade Head, Facilitator.
- **Moderation and quarantine** (`ReasoningFeedbackModeration`): the input passes the safeguarding phrase guard of every platform language before any network call (`safeguarding_disclosure`), then the shared moderation. The output must be JSON with exactly `structure`, `evidence`, `rebuttal`, `fallacies` (at most six, each a name and explanation) and `summary`, bounded, with no links, contact details, marks ("8/10", "75%", "12 points") or verdicts ("should win"). Anything else is quarantined (`reasoning_feedback_output_invalid`) and nothing is shown. `ReasoningFeedbackQuarantine` erases what is left of the review when quarantine retention ends.
- **Receipts.** `ai_usage` holds the reference `academic_reasoning_review:<id>`, no learner id and no text; the review's redacted text is cleared as soon as the call ends, whatever happened.
- **Prompt** `resources/ai/prompts/reasoning-feedback.json`; **corpus** `resources/ai/evals/reasoning_feedback.jsonl` (seven synthetic placeholder cases) and two shared red-team cases. These are synthetic scorer fixtures only, not model qualification.

## Awards

`AcademicAwards::link` presents one of the school's own award definitions to some of its own entrants through `AwardPresentations::present` (the awards module's own authority, eligibility, current-year, principal approval and notice rules), with the event "Debate: <motion> · Took part" or "· Placed". Placing needs the confirmed result and an entrant on the winning team or with a place in the school's submitted result. A learner is linked once per competition and basis; a replay presents nothing new. Another school's learners are never on this school's sheet (422), and another school's award definitions do not exist here (404).

## Endpoints

API v2, middleware `auth:sanctum`, `school.context`, `role.any`, `learner.approved`, `module:groups`, private no-store. Writes take `operation_id`. Answering and the team sheet use `/activities/fixtures/{fixture}/decision` and `/side`.

| Method and path | Who |
| --- | --- |
| `GET /activities/academic-competitions` | staff (managers: all; adjudicators and coaches: theirs) |
| `POST /activities/academic-competitions` | manager |
| `GET /activities/academic-competitions/{competition}` | participating staff |
| `PUT .../{competition}/details` | host manager |
| `PUT .../{competition}/adjudicators` | manager |
| `PUT .../{competition}/result` | adjudicator or manager |
| `POST .../{competition}/awards` | manager with awards authority |
| `POST .../{competition}/reasoning-reviews` | adjudicator or manager (throttled 20 a minute) |
| `GET .../{competition}/reasoning-reviews/{review}` | the adjudicator who asked, or a manager |
| `POST .../{competition}/reasoning-reviews/{review}/share` | the adjudicator who asked |
| `GET /activities/learners/{learner}/academic-competitions` | the learner's guardian (or the learner) |

Schemas: `AcademicCompetition`, `AcademicCompetitionDetail`, `AcademicResult`, `ReasoningReview`, `ReasoningFeedback`, `LearnerAcademicCompetition` and their writes. `FixtureResultState` gains `winner`.

## Screens (panel)

`/academic-competitions` (navigation "Academic competitions", next to Fixtures between schools; `activity-groups-manage`): the list and "Arrange an academic competition". `/academic-competitions/{competition}`: the competition, the host's details form, adjudicators, the result (draft or submit, with each entrant's score and place), AI feedback on reasoning (only when switched on) with sharing, and awards. The fixture page links to it and hides the score form for academic kinds.

## Acceptance tests (`Activities/AcademicCompetitionsTest`)

| Given | When | Then |
| --- | --- | --- |
| School A hosts a debate against Baobab | A's adjudicator keeps a draft, submits, Baobab submits a different then the same winner | none, awaiting, disputed (each sees the other's winner), confirmed; each school's families told in their own school; families see won or lost and no place |
| A quiz against a school outside NavigateEd | the host submits | the winner must follow the points; confirmed at once |
| Theo (no part), a parent, the team's coach, Baobab's manager | create, list, open, adjudicate, ask for AI feedback, change details | 403 or not found; the coach opens it but cannot adjudicate |
| Baobab (taking part) and Marula (no part) | open A's competition, review, draft result; award or score A's learners | none of A's learners, adjudicators, draft or feedback; 404 or 422; nothing written in A |
| AI switched on | Tara asks for feedback | the provider sees no school, team, learner or adult name; the receipt has no learner and only a reference; the result is untouched |
| An answer with a verdict or a score | | quarantined, not shown |
| AI off by default; school switch off; school budget spent; a safeguarding phrase | ask | 409; 403; unavailable with no provider call; quarantined with no provider call |
| Replay and changed request under one operation | ask | one provider call; 409 |
| Sharing | Tara shares her own words with Asha | only Asha's family sees them; feedback on the other school's entrant cannot be shared |
| Awards | taking part, then placing after the confirmed result | presented through the awards flow once each; Baobab cannot use A's award or reach A's learners |
| The API and the panel | the same flows | responses match openapi.yaml; panel pages for managers only |

## Not in this step

- App screens (adjudicator, parent and learner) and the Dart client regeneration.
- More than two schools in one competition (olympiads and tournaments with many schools), rounds as separate records, and adjudication by a neutral third school.
- A retention schedule for completed AI feedback (the redacted text is already cleared; the feedback stays with the review).
- Sharing AI feedback automatically, or any AI output to learners or families.
- Qualifying a provider, real evaluation observations and red-team acceptance for `reasoning_feedback`.
