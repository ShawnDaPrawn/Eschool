# Content production pipeline

Action Plan 3.4, with the quality-signal part of SA.16. Builds on the platform
content library ([content library](content-library.md)). Owner role: Backend
and Admin UX (HQ), Learning, AI.

The library needs thousands of reviewed questions and lessons. Content staff
can now ask AI for drafts of library items; the drafts arrive as drafts,
labelled as AI drafts, and go through the library's existing review: a
reviewer assigned to the subject, who did not ask for the draft, must approve
a version before it can be published. Learners' answers across schools then
show which published questions look wrong, as counts and rates that never
point at a school or a learner, and HQ opens a new draft from that list.

Model calls are off across the platform and no provider is qualified yet
(Action Plan 3.1). The pipeline is fully built and tested with a local probe
in place of the provider; while inference is off, HQ is told so and nothing is
written.

## User stories

- As Content staff, I ask AI for up to five practice questions, or one
  lesson, for a library topic, from a source I choose and with a brief, and
  get drafts I can check, change and send for review.
- As Content staff, while AI is switched off, I see that plainly and write
  items by hand.
- As a reviewer, I can show only AI drafts, or only items learners' answers
  have flagged, in my review queue, and every AI draft says so.
- As HQ, I see who asked for an AI draft and who approved it, on the item and
  in the audit log.
- As HQ, I see which published library questions learners find far harder or
  far easier than expected, without ever seeing a school or a learner, and I
  open a new draft of one from that list.

## Rules

- **Who.** Asking for drafts, the "Needs attention" page and opening a new
  draft from it need `library.manage` (Content, Owner). The queue filters need
  `library.review` (Content reviewer, Owner). Finance, Sales, Support,
  Operations and Content reviewers get 403 on the manage routes; school
  accounts never reach `/saas`; every service refuses while a school is the
  current school (403).
- **Through the gateway.** `AiGateway::runPlatform` (`PlatformInference`) is
  the HQ path of the AI gateway: the platform switch (`ai.inference.enabled`),
  the feature switch (`ai.platform.features.library_drafting.enabled`), a
  qualified provider (`ai.providers.*.qualified`), the registered prompt
  (`resources/ai/prompts/library-drafting.json`), moderation of the input and
  the output (the feature's moderator, OpenAI moderation by default), and
  metering with daily caps. It runs only with no school set, for platform
  staff whose role holds the feature's ability, and meters into
  `platform_ai_usage`, never a school's `ai_usage`. Switches, role and caps
  are read again between stages, so a change stops a call in flight.
- **HQ's caps.** `platform_daily_cents` and `platform_daily_tokens` (all HQ
  drafting in a day) and `actor_daily_cents` and `actor_daily_tokens` (each
  person), in `config/ai.php` (`ai.platform.features.library_drafting`),
  integer cents of `ai.billing_currency`. Today's rows are locked while the
  cap is checked, so two requests cannot both pass it. A request over a cap is
  refused before the provider is called. `cents_per_million_tokens` is unset
  until a provider is qualified, so the feature cannot run without a price.
- **Refused while off.** While inference is off, the feature is off or no
  provider is qualified, the framework page shows why in place of the form
  and a request is refused with that message (`drafting` error); no request
  row, usage row or item is written and the provider is never called.
- **What the model sees.** Library data and the brief only: the framework
  code, subject code, grade name, topic, its prerequisite topics' names, the
  chosen source (title, kind, licence, attribution), the kind, the count and
  the brief. Never a school's material, a school or a person.
- **What it may draft.** Practice questions (one to five, in the daily
  practice format) or one lesson. Past-paper questions and diagnostics stay
  human work.
- **What comes back.** The answer must be exactly the expected JSON with
  exactly the asked number of items; each is checked by `LibraryContent`
  exactly as a typed form is (option codes are lower-cased), and plain text
  only (no links or markup). Anything else is refused whole
  (`library_output_invalid`): nothing is added.
- **Drafts.** Each item gets the request's topic and source; version 1 is a
  `draft` with `origin` `ai_draft` and the request's id, created by (and with
  the first editor being) the person who asked. Nothing is published: the
  item has no published version until a reviewer approves and Content
  publishes.
- **Never self-approved.** The person who asked is an editor of the version,
  so the existing rule refuses them as its reviewer (403), whoever submits it.
- **Origin is fixed.** A version's `origin` and request never change, even on
  a draft (409). A new draft started from an AI-drafted version keeps its
  origin and request, however much staff edit it.
- **Provenance.** Every item names its topic and source, fixed for every
  version. The item page shows, per version: "Written by staff from
  &lt;source&gt; (&lt;licence&gt;)", or "AI draft from &lt;source&gt;, asked for by
  &lt;name&gt; — reviewed by &lt;name&gt;" (or "not yet approved by a reviewer"), with
  the brief. The framework page, the queue and the attention list mark AI
  drafts.
- **Idempotent.** The form carries an operation id. Sending it again returns
  the first request's outcome with no second model call; anyone else, or a
  different request, with the same id is refused (409).
- **Moderation.** Flagged input or output holds the request back (`held`): the
  usage row keeps the flag codes only, nothing is added to the library.
- **Quality signals.** `LibraryQuality` counts, in the database, learners'
  daily-practice answers (`practice_reviews` joined to
  `practice_set_entries.library_item_version_id`) on each item's current
  published version, across schools. A group with fewer than 20 learners or
  fewer than 3 schools never leaves the query (`HAVING`). A question is
  flagged when under 30% or over 95% of answers are right. HQ sees the item,
  its topic, the percentage right and a learner-count band (20 to 49, 50 to
  99, 100 to 999, 1000+): never a school's name or id, a learner, an answer or
  a date. Nothing is stored and nothing is copied into the library. Answers to
  a school's own questions are never counted.
- **Needs attention.** `/saas/library/attention` lists flagged items, furthest
  from the expected band first. "Open a new draft" starts the next version as
  a draft with the published content unchanged (`library_draft_started`,
  reason `needs_attention`); the published version stays what learners see.
  Refused (409) while the item already has a draft or a version in review.
- **Review queue filters.** Everything waiting, AI drafts, or Needs attention
  (items with a flag). Filters apply before paging.
- **Audit.** `library_ai_draft_requested` (topic, source, kind, count; not
  the brief), `library_ai_drafted` (completed with item ids, or failed or held
  with the code), `library_item_created` with `origin` and `ai_draft_id`.
  `library_reviewed` and `library_published` now carry `origin`, and for an AI
  draft `reviewed_by` (the reviewer's name). No school.

## Data

| Table | Tenancy | Holds |
| --- | --- | --- |
| `platform_ai_usage` | global | HQ model calls: who, feature, provider, model, operation, day, tokens and cents reserved and used, currency, prompt key, version and hash, flag codes, failure code, output hash. Never a prompt or an answer |
| `library_ai_drafts` | global | operation id, request hash, who asked, topic, source, kind, count, brief, state (pending, completed, failed, held), failure code, usage row, item ids |

Columns added to `library_item_versions`: `origin` (`staff` by default, or
`ai_draft`) and `library_ai_draft_id`. Migration
`2026_11_07_100000_add_library_drafting_and_provenance`; its `down` refuses
while AI drafts or HQ metering exist.

## Acceptance tests (`Platform/LibraryContentPipelineTest`)

The provider and moderator are local probes and stray HTTP is refused.

| Given | When | Then |
| --- | --- | --- |
| Inference on (test only), a Maths reviewer | Content asks for two practice questions over HTTP; sends the form again; the Owner reuses its id; publishes before review; submits; the reviewer filters to AI drafts and approves; Content publishes | two items, version 1 `draft`, `ai_draft`, linked to topic, source and request; metered in `platform_ai_usage` only; input and output moderated; the model saw library data only; replay makes no second call; 409 for the Owner; 409 before approval; then published; the item page and the audit name the reviewer; no school on any audit row (`test_content_staff_get_ai_drafts_that_are_published_only_after_a_subject_reviewer_approves`) |
| The Owner, a Maths reviewer, asks; Content edits and submits | the Owner reviews; the origin is changed; a reviewer approves; Content starts the next draft | 403 (service and HTTP), nothing written; 409; the next draft keeps `ai_draft`; a staff item reads "Written by staff from …" (`test_the_person_who_asked_for_an_ai_draft_can_never_approve_it_and_its_label_stays`) |
| Shipped config; then the feature on with the platform switch off; then a provider not qualified | Content opens the pages and asks | the message, no form; `drafting` error each time; nothing written, no call, no HTTP; the shipped config is off and unqualified (`test_while_inference_is_off_drafting_is_refused_with_a_clear_message_and_nothing_is_written`) |
| Inference on | output flagged; the wrong number of items; a link in a question; HQ's daily cap; a person's daily cap; two lessons; a past-paper question | held with the flag code only; failed; failed (and the HTTP message); refused before the provider; validation errors; no item added (`test_moderation_a_bad_answer_and_hq_caps_add_nothing_to_the_library`) |
| Finance, Sales, Support, Operations, Reviewer; a school Super Admin and teachers of both schools; Content inside each school | ask for drafts, open the attention page, open a new draft | 403 everywhere, nothing written, no call (`test_wrong_hq_roles_and_school_accounts_are_refused_and_nothing_is_written`) |
| Answers in three schools: 25 learners at 20% right; 30 at 100%; 30 at 60%; 40 in two schools; 19 in three; 90 on a school's own question | read the signals and the page; open a new draft; again; submit it and filter the queue | only the first three counted, two flagged; the page shows the rate and band and no school name; nothing written; version 2 drafted from the published content, still live; 409; listed under Needs attention (`test_weak_items_are_flagged_only_for_large_groups_and_never_show_a_school_or_a_learner`) |
| The schema | list the new tables; write HQ metering inside a school | no `school_id`, listed as global; 403 (`test_new_tables_belong_to_no_school`) |

`SaasPanelTest` counts the 3 new `/saas` routes (126 in all).
`Unit/Ai/EvaluationCorporaTest` now also covers HQ features' corpora
(`resources/ai/evals/library_drafting.jsonl`).

## Endpoints (HQ panel)

| Method | Path | Name | Ability |
| --- | --- | --- | --- |
| POST | `/saas/library/drafts` | `saas.library.drafts.store` | `library.manage` |
| GET | `/saas/library/attention` | `saas.library.attention` | `library.manage` |
| POST | `/saas/library/items/{item}/redraft` | `saas.library.items.redraft` | `library.manage` |
| GET | `/saas/library/reviews?filter=all\|ai_draft\|attention` | `saas.library.reviews` | `library.review` |

## Not in this step

- **Switching it on.** No provider is qualified (3.1) and no price is set;
  the feature stays off in `config/ai.php`. Qualification needs the
  `library_drafting` evaluation corpus run against a real provider.
- **A teacher panel outside HQ.** Reviewers are platform staff with the
  Content reviewer role (as in 3.3). Inviting outside teachers as reviewers is
  a platform-staff invitation, not new code.
- **"Unclear" reports from learners.** No such signal exists in the apps yet;
  the signals use right and wrong answers only.
- **HQ caps edited in `/saas/ai`** and HQ drafting spend on the AI control
  centre (SA.15): the caps are config values for now.
- **AI redrafts of an existing item.** A flagged item opens a new draft by
  hand; AI drafting makes new items.
- **Background generation.** A request runs in the HTTP request, as school
  teaching drafts do; a queue can follow when real providers are slower.
