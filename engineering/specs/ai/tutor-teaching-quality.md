# Tutor teaching quality

Action Plan 3.10. Owner role: Backend. Builds on the subject tutor (`tutor.md`), school stages (`../platform/school-stages.md`) and the inference gateway (`gateway-foundation.md`, `feature-inference.md`).

## Why

The first tutor prompt gives a short hint, a question and a correction, the same way for a Grade 4 and a Grade 12 learner, reads only the first 3,000 characters of the chosen content and forgets the learner between sessions. This step makes it teach better without loosening any of its safety boundaries: the same subject lock, the same structured answer, the same caps, moderation and quarantine.

## User stories

- As a Grade 4 learner, the tutor explains in short sentences with everyday examples, and shows me a worked example of a similar problem when I am stuck, without doing my exercise for me.
- As a Grade 10 learner, the tutor uses the proper subject terms and exam-style wording.
- As a learner, I can "explain it back": I tell the tutor in my own words how something works, and it tells me kindly what is right and what to fix, without a mark.
- As a learner, the tutor remembers which topics I practised in earlier sessions in this subject, and I can see and clear that memory.
- As a parent, I can see what the tutor remembers about my child (topics and counts, never their chat) and clear it.
- As a learner whose home language is Afrikaans or isiZulu, I can ask for the explanation in that language once the school platform has switched it on.
- As a learner asking about one part of a long document, the tutor reads the section that answers my question.
- As the owner, I can keep the original tutor prompt (v1) selected while v2 is being qualified.

## Prompt versions

- `resources/ai/prompts/tutor.json` (key `tutor`, version 1.2.0): v1. Unchanged by this step; the guided learning path ([learning path](../learning/learning-path.md)) added its one `learning_path` instruction (1.1.0 to 1.2.0).
- `resources/ai/prompts/tutor-v2.json` (key `tutor-v2`, version 2.1.0): v2. 2.1.0 carries the same guided learning path instruction as v1 1.2.0: when `approved_context.learning_path` has `lead` true (the teach step) the tutor leads the lesson in small steps; on any other step it gives hints only, and it never gives the path's practice or check answers.
- `ai.inference.features.tutor.prompt` selects the version (`AI_TUTOR_PROMPT`, default `tutor-v2`). `TutorPrompts` allows only these two keys; anything else is refused as `prompt_not_allowed`.
- Each turn stores the prompt key it was built for (`tutor_messages.prompt_key`; older rows are v1). The feature policy sends that key, and the gateway refuses it if the configuration has since changed, so a turn is never answered by a prompt it was not built for. Usage receipts record the prompt key, version and hash as before.
- v1 keeps its exact original input and context (first 3,000 characters, no stage, memory, section or language). v1 refuses `explain_back` turns and home-language requests with 422.

## v2 behaviour

1. **Stage and grade.** The context carries `learner.stage` (from `SchoolStages::forLearner`, Action Plan 4.1: `junior` or `high_school`; early-years learners cannot use the tutor) and `learner.grade` (the teaching group's grade name). No age or date of birth is sent. The prompt adapts language and examples to them.
2. **Worked examples and misconceptions.** In the hint phase the tutor may give a short worked example of a similar problem with different numbers, labelled "Worked example:", and name one common misconception. It never solves the learner's own exercise.
3. **Explain it back.** A turn may have `turn_type: explain_back` (default `ask`). Its phase is `explain_back`; the tutor says what is right, corrects one misconception and asks a follow-up. `correction` is accepted for it as for a turn with working; an ordinary question without working still refuses `correction`. Turn type is part of the turn's request identity.
4. **Warmer tone, same schema.** The answer is still exactly `subject_token`, `kind` (`hint`, `question`, `correction`, `refusal`), `text` and `question`, with the same byte limits and the same quarantine of any extra field. Warmth, examples and misconceptions live inside `text` and `question`. The prompt forbids comparisons and rankings.
5. **Memory** (below), current subject only, without the session being taught.
6. **Relevant section** (below).
7. **Home language** (below).

## Memory across sessions

`TutorMemory` derives the memory; no model writes it and it holds no chat text.

- Per subject: earlier sessions, learning steps, explain-back turns, the published topics practised (up to 10) and the last active day.
- **Learner-owned and school-scoped.** Only the learner's own sessions at the current school; `tutor_memories` (school-owned) holds only the learner's clear mark (`cleared_at`, `cleared_by`, `cleared_by_role`).
- **Who can see and clear it:** the learner (`GET /api/v2/tutor/memory`, `POST /api/v2/tutor/memory/clear`) and each active guardian of the learner (`GET|POST /api/v2/tutor/parent/learners/{learner}/memory[/clear]`). Nobody else: teachers, other learners, other guardians and other schools get 404. These routes stay open while the school has the tutor module off, and a guardian does not need a current approval to clear. Each clear is audited (`tutor.memory_cleared`).
- **Clears are idempotent by operation.** Each clear's `operation_id` is kept in `tutor_memory_clears` (school-owned, unique per school and operation). Replaying it (a retried request) changes nothing and writes no audit entry, so turns made since the original clear stay in the memory; the same operation used by another person, for another learner or on the other route is refused with 409.
- **Retention rule:** only turns from the current school year and the last `ai.tutor.memory_days` days (90) count; older turns are never shown or used. After a clear only newer turns count. Earlier chat is not deleted by a clear; it keeps its own tutor retention.
- **Never quarantined content:** only `completed` turns with a `hint`, `question` or `correction` answer count; quarantined, purged and failed turns never do, nor does any turn whose usage receipt is quarantined or purged, even if the message row still says completed.
- In a v2 turn, `approved_context.memory` holds the current subject's counts and up to five topic names (minimized); its digest is in the turn's manifest and source references. Memory is not a source to reauthorize: clearing it never hides earlier turns.

## Relevant section through embeddings

- `TutorSections` splits each content version at its Markdown headings into sections of at most 3,000 bytes (at most 8 per version).
- For a new v2 turn, `TutorRetrieval` embeds the learner's minimized question and each section through the gateway as the separate registered feature `tutor_retrieval` (`TutorRetrievalFeaturePolicy`, capability `embed`, prompt `tutor-retrieval`), and gives the tutor the section with the highest cosine similarity. A section's vector is replayed from its completed receipt for the rest of the session; its operation identity includes the section's index and hash, so identical sections are separate requests. At most 16 section vectors are requested per turn.
- The policy accepts only the learner's own open session and either their question for that turn or a section of a version the session already selected; it rebuilds the input and the operation identity itself.
- Gateway rules apply unchanged: inference and provider switches, school pause, HQ and school caps, moderation (the tutor's local safeguarding guard and provider moderation of each input), metering and quarantine. `tutor_retrieval` is a personal feature, so its spend joins the tutor's pool and stays out of the school's daily total.
- **Clean fallback:** when `tutor_retrieval` is off (the default), unconfigured, capped, paused, failing or flagged, or a section changed, the tutor reads the first 3,000 characters exactly as v1 does. The manifest records the method (`embeddings`, `opening` or `single_section`). A content version with one section needs no vector. A safeguarding disclosure goes to the local guard with no network call, so it is never embedded.
- Dialogue reuse compares sources only, so choosing a different section does not drop recent dialogue.

## Home-language explanations

- `language` on a turn may be one of the platform's offered languages besides English (`af`, `zu`, `TutorLanguages::OFFERED`) that the deployment lists in `ai.tutor.home_languages` (`AI_TUTOR_HOME_LANGUAGES`, empty by default). Anything else is refused with 422.
- The default is empty because Action Plan 3.2 requires each language's safeguarding phrase list to be professionally reviewed before it is offered in the tutor.
- The tutor writes `text` and `question` in that language, with the English subject term in brackets the first time. A language switched off after a turn was written fails that turn (`tutor_language_unavailable`) rather than sending it.

## Data changes

Migration `2026_11_08_310000_add_tutor_teaching_quality`:

- `tutor_messages.prompt_key` (nullable), `turn_type` (default `ask`), `explain_language` (nullable).
- `tutor_memories` (school-owned, listed in `SchoolOwnedTables`): `school_id`, `learner_id`, `cleared_at`, `cleared_by`, `cleared_by_role`; unique per school and learner.
- `tutor_memory_clears` (school-owned): each clear by `operation_id` (unique per school), with learner, person, role and time.
- `down()` refuses while any memory mark, recorded clear or turn using the new columns exists.

## Endpoints

- `GET /api/v2/tutor/configuration` gains optional `tutor_version`, `explain_back_enabled`, `home_languages` and `memory_days`.
- `POST /api/v2/tutor/sessions/{session}/messages` accepts optional `turn_type` and `language`; turns gain optional `turn_type` and `explain_language`.
- `GET /api/v2/tutor/memory`, `POST /api/v2/tutor/memory/clear`, `GET /api/v2/tutor/parent/learners/{learner}/memory`, `POST /api/v2/tutor/parent/learners/{learner}/memory/clear` (`auth:sanctum`, `school.context`, `role.any`, private no-store responses, writes throttled). All in `openapi.yaml`. The Dart client (`packages/eschool_api`) still needs regenerating.

## Evaluations

`tutor.jsonl` gains nine v2 cases (worked example, misconception, explain-back feedback and injection, junior plain language, memory probes, home language, no ranking). `tutor_retrieval.jsonl` is the new feature's corpus, and `redteam.jsonl` gains a retrieval content-injection case and a cross-learner memory probe. HQ's AI page counts the corpora (now 19 tutor cases). These are synthetic inputs; model qualification (Action Plan 3.1) remains separate.

## Acceptance tests

`tests/Feature/Learning/TutorTeachingQualityTest.php`:

| Test | What it proves |
| --- | --- |
| `test_v2_turn_adapts_to_stage_and_grade_with_a_warm_structured_answer` | Happy path: v2 sends stage and grade, no date of birth, the same four-field answer; receipts record `tutor-v2` 2.0.0 |
| `test_v1_stays_selectable_with_its_original_context_and_refuses_v2_options` | v1 still works with its exact input and first-3,000-character context, uses no embeddings, refuses explain-back and home language |
| `test_explain_back_turn_gets_feedback_on_the_learners_own_explanation` | Explain-back phase and feedback; an ordinary question still refuses a correction |
| `test_home_language_explanations_are_limited_to_offered_and_enabled_languages` | Off by default; only af and zu, and only when listed |
| `test_embeddings_choose_the_relevant_section_and_replay_section_vectors_in_the_session` | The fake provider's vectors choose the decimals section; section vectors replay; dialogue is reused |
| `test_retrieval_falls_back_to_the_opening_when_off_failing_or_for_a_disclosure` | Embeddings off, failing or capped: the v1 opening; a disclosure sends nothing |
| `test_memory_summarises_earlier_sessions_for_learner_guardian_and_the_next_v2_turn` | Learner and guardian see the same derived memory without chat text; the next v2 turn gets it; still visible with the module off |
| `test_memory_never_includes_quarantined_or_flagged_turns` | Input-quarantined, output-quarantined and later-flagged turns never count |
| `test_learner_and_guardian_can_clear_memory_and_retention_drops_old_turns` | Clearing by either is audited and only newer turns count; after 90 days nothing counts |
| `test_wrong_role_wrong_owner_and_wrong_school_cannot_read_or_clear_memory` | Teacher, another learner, another learner's guardian and School B are refused; nothing of theirs changes |
| `test_memory_and_teaching_options_over_http_match_the_typed_contract` | HTTP routes match `openapi.yaml`; expected-school and guardian checks |
| `test_identical_sections_get_their_own_vectors_and_still_use_embeddings` | Two identical sections get separate vector operations (the index is in the identity), so retrieval still works |
| `test_a_retried_clear_replays_without_erasing_newer_turns_on_learner_and_guardian_routes` | On both routes a retried clear changes nothing and writes no audit; another person's operation gets 409 |

The existing tutor suites run on v2 by default and still pass; `HqAiControlsTest` counts 19 tutor cases.

## Not in this step

- App screens for explain-back, memory and the language choice (Flutter cannot be built here), and the regenerated Dart client.
- Real-model qualification of v2 and of the embedding model (Action Plan 3.1), and teacher-panel rating of teaching quality.
- Switching on Afrikaans or isiZulu explanations: waits for the reviewed safeguarding phrase lists (Action Plan 3.2).
- Voice for home-language explanations (Action Plan 3.12).
- A shared per-school cache of section vectors: vectors are replayed per learner session.
- Telling the tutor the learner's current learning-path step (Action Plan 3.9, open in another pull request): it would join the v2 context next to `learner`.
