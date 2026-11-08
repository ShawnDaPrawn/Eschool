# Safeguarding phrase guard in every platform language

Action Plan 3.2. Owner role: Backend. Builds on the subject tutor's local safeguarding guard (`tutor.md`), home-language explanations (`tutor-teaching-quality.md`), the quarantine contract (`quarantine-contract.md`) and HQ roles (`../platform/platform-roles.md`).

## Why

The tutor's local guard catches a learner's message that signals risk (self-harm, abuse, sexual violence) on the server, before any AI call, and sends it to the private safeguarding queue with fixed guidance for the learner. Until now the guard had one short English list. Learners write in Afrikaans and isiZulu too, and often mix languages in one message. Afrikaans and isiZulu explanations must also not be offered in the tutor until professionals have reviewed the phrase lists for those languages.

## User stories

- As a learner who writes in Afrikaans, isiZulu or a mix with English, a message that signals I am at risk is caught and I am shown where to find help, and it never goes to an AI provider.
- As Navigate Ed's content or AI staff, I can see each language's list and whether it has been reviewed, and record a professional's review (who, when, a note).
- As the owner, I know a home language is offered in the tutor only after its list has been reviewed, and that a draft list still protects learners.

## Phrase lists

- One file per platform language: `resources/ai/safeguarding/en.json`, `af.json`, `zu.json` (`SafeguardingPhrases::LANGUAGES`). Each lists phrases under the categories of the original English list: `self_harm`, `abuse`, `sexual_violence`. A missing or malformed list, an unknown or missing category, or a wildcard shorter than four or five letters fails closed: the turn fails and nothing is sent.
- English keeps its five original phrases exactly.
- Afrikaans and isiZulu ship as **draft**: written as detection phrases only (no instructions, no methods), covering the same categories, with code-switched forms (for example an Afrikaans sentence with an English verb). They need review by safeguarding professionals and fluent speakers.
- isiZulu joins subject, object and reflexive markers to the verb (`ngizozibulala`), so a list word may carry `*`: `*stem*` matches inside a word, `*end` at its end, `start*` at its start. Stems were chosen to avoid ordinary words (for example `izibulali-zinambuzane`, insecticides, and `izilimazo`, injuries, do not match).

## Matching

`SafeguardingPhrases::categories($text)` checks the text against **every** list, reviewed or draft. Both the list and the message are normalised the same way:

- lower case; diacritics removed (`beëindig` = `beeindig`); apostrophes and soft hyphens removed (`'n` = `n`);
- repeated letters count once (`killl`, `myselfff`);
- digit look-alikes inside a word (`k1ll`, `s3lf`), reading `1` as both `i` and `l`;
- hyphens and dots between letters read both as a space and as nothing (`uku-zibulala`, `sexually-assaulted`);
- words run together or split apart (`my self`, `killmyself`, `dood maak`): a phrase without wildcards matches any run of up to eight consecutive words that, joined, equals the phrase joined. Phrases still match whole words only, so `skill myself` and `end my lifetime` do not match.

Because all lists are checked at once, a mixed English/Afrikaans or English/isiZulu message is caught whichever language carries the signal. `TutorModeration::localFlags` (tutor turns, exam feedback and retrieval) returns only `safeguarding_disclosure`, never the phrase or language that matched.

## Review state

- `safeguarding_phrase_reviews` (platform table, no `school_id`, append-only): `language`, `outcome` (`reviewed` or `changes_needed`), `list_sha256` (the list the review covered), `reviewer` (name, and role or organisation), `reviewed_on`, `note`, `recorded_by`, `created_at`.
- A language is **reviewed** only while its newest row is `reviewed` **and** its hash equals the list as it is now. Any change to a list makes it draft again; `changes_needed` makes it draft at once.
- Draft never weakens detection. What draft blocks is the tutor: `TutorLanguages::enabled()` offers a home language only when the deployment lists it in `ai.tutor.home_languages` **and** its list is reviewed. That one rule drives the configuration's `home_languages`, the turn's `language` validation (422) and the send-time check in `TutorFeaturePolicy` (`tutor_language_unavailable`), in the service and the API alike.
- English is the tutor's own language: its review is shown and can be recorded, but does not switch the tutor on or off.

## HQ

- New platform ability `safeguarding.phrases`, held by Content (and Owner).
- `GET /saas/safeguarding-phrases`: each language's state, whether the tutor offers it, the latest review, the history, the list fingerprint and its phrases, **read-only**.
- `POST /saas/safeguarding-phrases/{language}/reviews` (`en`, `af`, `zu`): outcome, reviewer, date (not in the future), note (10 to 1,000 characters). Audited as `platform_safeguarding_phrase_review_recorded` with the language, state before and after, outcome, reviewer, date, note and list hash, never a phrase, and no school.
- There is no HQ route that changes a phrase. Lists change only through a reviewed code change, which makes the language draft again.

## Privacy and safety

- The phrases are never sent to an AI provider, never in a prompt, never logged and never audited. The guard runs before any network call, so a quarantined message reaches no provider (no moderation, embedding or completion).
- The review table names no school and holds no learner message.

## Tests

| Test | Covers |
| --- | --- |
| `TutorSafeguardingLanguagesTest::test_each_languages_list_covers_every_category_and_each_phrase_is_caught` | every phrase of every list and realistic disclosures per language (case, diacritics, repeated letters, split words, look-alikes); English unchanged |
| `::test_mixed_language_messages_are_caught_and_ordinary_schoolwork_is_not` | English/Afrikaans and English/isiZulu mixes, switches inside a phrase; ordinary schoolwork and every en/af/zu app label pass |
| `::test_draft_lists_are_still_checked_and_nothing_reaches_the_provider` | draft af/zu disclosures quarantined by service and API, no request leaves the server, School B sees nothing |
| `::test_a_draft_language_cannot_be_chosen_for_tutoring_until_reviewed` | service and API refuse a draft language; a review of an earlier list does not count; `changes_needed` and the deployment list still apply |
| `::test_the_phrases_never_reach_the_provider_or_the_logs` | provider requests and log messages never contain a phrase |
| `HqSafeguardingPhrasesTest` | content staff and owner record audited reviews; wrong roles, school accounts of either school refused; validation; no route writes a phrase |

## Not in this step

- Professional and fluent-speaker review of the Afrikaans and isiZulu lists (owner to arrange), and switching those languages on (`AI_TUTOR_HOME_LANGUAGES`).
- Lists for the other legacy app interface languages (Urdu, Turkish, Arabic, Spanish, Portuguese, French, Indonesian and so on). They are not platform languages and are not offered in the tutor; adding one is a new file plus an entry in `SafeguardingPhrases::LANGUAGES`, and the guard then checks it on every message.
- A clinical classifier: this remains a conservative phrase guard beside provider moderation.
- App screens (Flutter cannot be built here).
