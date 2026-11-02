# Subject choice and pathway planning

The owner retained the Build Bible's Grade 9 subject-choice guide, sourced
admission reference data and projected admission score. This feature extends the
existing school, guardian, subject and released-marks boundaries. It does not
predict an institution's admission decision or write academic marks.

Each school appoints holders of `pathways-manage` to maintain its reference
catalogue. Every published programme or subject-choice guide requires an HTTPS
source URL, source title, source year, review date and expiry date. The school
owns verification against that source; records identify the reviewing staff
account internally. No invented institution entries or universal scoring policy
are seeded. Source revisions are immutable and audited; withdrawal removes the
record from fresh family use. Historical revisions are retained. Expired sources
cannot support a new projection. The Bible catalogue module `pathway` is separately switchable, off by
default, and does not activate external AI or payment providers.

Programme rules explicitly record required subjects/minimum percentages, which
subjects count, the source's score bands, number of best subjects to count,
excluded subjects and minimum total. All percentages use integer basis points.
Scoring uses the published rule, including its stated exclusions; Life
Orientation is never silently included or excluded. Unknown marks remain unknown
and cannot become zero or a passing score. A complete score requires all relevant
chosen candidates to have marks; incomplete calculations show known evidence and
missing subjects separately. Meeting a published minimum is labelled as such,
with no offer or admission guarantee.

An approved current learner and their current guardian may read current school
references and use released own-child marks for an explicitly chosen school
year/period, or enter a clearly labelled hypothetical scenario. Hypothetical
marks and selections are calculated transiently, never persisted or applied to
school marks. A Grade 9 guide carries sourced explanatory text, compulsory
subjects and bounded elective groups; the client shows valid combinations and
links to the source. No learner records are exposed through staff reference
maintenance. An ordinary teacher, facilitator, unrelated parent, other school,
platform account or impersonation cannot manage reference data or read another
learner's projection.

The responsive manager panel creates and revises references through labelled
fields, publishes/withdraws explicit revisions and shows review/expiry state.
The existing learner/Parent app gets the same shared private planner, current
child and source selection, guide and projected-score evidence. Source/context
changes and app background erase private marks and scenarios; late responses
cannot restore them. Read-only schools can calculate without persisting data.

Verification includes source validation/versioning/idempotent publication,
expiry and withdrawal, integer boundary bands, unknown marks and exclusions,
released-only family access, wrong role/school/owner, inactive guardian/learner,
platform and impersonation exclusion, schema/route contracts, SDK reproduction,
phone/desktop widget coverage, real learner/Parent browser journeys and manager
Playwright at 390/820/1280 pixels. Unrun school source review, fluent wording,
physical devices, staging and production acceptance remain separately recorded.

## Career profiles and "what would it take" (Action Plan 4.9)

Pathways grows in two directions: earlier, so a learner and the adults around
them can look at a career well before the Grade 9 choice, and in reverse, so
they can start from a career and see what it would take. This step is the
server and panel slice.

### User stories

- As a catalogue manager (`pathways-manage`), I publish a career profile from a
  dated public source: what the work involves, the school subjects it needs,
  the curriculum topics that matter, and its routes. Routes can be university,
  TVET college or learnership routes, each with its own cited source.
- As a counsellor or grade head with `pathways-manage`, I open any current
  learner of my school, choose a career profile and see what it would take.
- As a teacher, I open the same view for the learners in the teaching groups I
  am currently allocated to.

### Decision: the catalogue is school-owned

The existing catalogue (`admission_requirements`) is school-owned: each
school's `pathways-manage` holders verify and publish their own sources. Career
profiles follow the same model rather than becoming a platform (HQ) catalogue.
They link the school's own subject ids, mastery topics and programme sources,
which are all school-owned, so a shared catalogue would need a separate mapping
layer and HQ curation that does not exist yet. A platform-curated starter set
that schools copy into their own catalogue is listed under "Not in this step".

### Rules

- Every career profile revision has a source title, a public HTTPS source
  address without credentials, a source year, a review date (not in the future)
  and an expiry date at most a year after review. Each route also cites its own
  source title and public HTTPS address. Revisions are immutable and kept;
  writes are idempotent per operation id and refuse a stale revision (409).
- A profile lists 1 to 20 subject requirements. Each names one subject, or
  several where any one meets the requirement, an optional sourced minimum
  percentage (integer basis points) and whether the source says required or
  recommended. A subject appears once per profile.
- Linked topics (up to 50) must be published mastery topics of the profile's
  subjects. A profile has 1 to 10 routes of type `university`, `tvet` or
  `learnership`. Only a university route may link a programme source, and only
  one from the same school.
- A profile past its expiry date is stale. Stale profiles are flagged in the
  manager listing ("This career profile needs review.") and in the guidance
  view, and are marked "needs review" in the picker. A withdrawn profile leaves
  the guidance view (409).
- A subject retired after a profile was published stays readable as retired
  ("No longer offered at this school."). Its topics are skipped, and the other
  requirements, topics and routes still show.
- The guidance view is read-only. It shows the profile's subject requirements
  next to the learner's current subjects (subjects of the active teaching
  groups the learner is a current member of) and the linked topics with the
  learner's staff mastery level. Starting points come from
  `MasteryService::roots`, at most three per subject.
- Mastery evidence keeps its own rule: it shows only where
  `MasteryPolicy::staff` lets the reader see one of the learner's current
  groups in that subject. Otherwise, or when the learner does not take the
  subject, or when mastery is switched off, every linked topic shows as
  unknown. Missing evidence is never zero.
- Wording is encouraging and factual. The view never says a learner is or is
  not suitable, and never recommends or limits subject choices or streams. A
  subject the learner does not take reads "Not in current subjects yet. Worth
  talking through the options and timing with the learner and family."
- Parents, learners, platform accounts, impersonation and staff who neither
  hold `pathways-manage` nor teach a current group are refused (403). A teacher
  opening a learner they do not teach, or anyone opening another school's
  learner or profile, gets 404.

### Data

- `career_profiles`: school-owned head row (uuid, status, revision, current
  version, expiry).
- `career_profile_versions`: immutable revisions with the source fields and a
  `details` JSON of `subjects`, `topic_ids` and `routes`, plus the reviewing
  staff account.
- Idempotent writes reuse `pathway_operations`.
- Navigation: "What would it take" (`pathways-panel.guidance`), shown to staff
  who pass the guidance rule above.

### Acceptance tests (`tests/Feature/Pathways/CareerProfilesTest.php`)

| Test | Checks |
| --- | --- |
| `test_manager_publishes_a_sourced_profile_with_tvet_and_learnership_routes_and_history_is_immutable` | Happy path: publish, idempotent retry, revision, stale revision and changed retry refused, revisions immutable |
| `test_university_route_links_a_programme_and_every_route_needs_a_public_source` | Programme link shown in the view; HTTP or IP route sources and a programme on a TVET route refused with nothing written |
| `test_what_would_it_take_compares_subjects_and_shows_mastery_gaps_with_unknown_never_zero` | Happy path: subjects against current subjects, mastery levels, an unassessed starting point stays unknown, panel page wording, learner search, no writes |
| `test_panel_publishes_a_profile_and_flags_it_once_its_review_date_passes` | Panel form publishes; stale profile flagged in the listing and the view; withdrawn profile leaves the view |
| `test_a_retired_subject_shows_as_retired_and_the_rest_of_the_profile_stays_readable` | A soft-deleted subject requirement, with or without linked topics, never turns the view into a 404 |
| `test_wrong_role_cannot_publish_profiles_or_open_the_guidance_view` | Teacher, parent and learner cannot publish; parent and learner cannot open the view; a teacher with no groups is refused; counts unchanged |
| `test_wrong_school_ids_are_refused_and_school_b_rows_are_unchanged` | School B subject, profile and learner ids refused from School A; School B learners never listed; School B rows unchanged |
| `test_wrong_owner_teacher_cannot_open_a_learner_they_do_not_teach_and_unshared_evidence_stays_unknown` | A teacher of another group gets 404; a manager who does not teach the learner sees the profile but the maths evidence stays unknown |

### Not in this step

- The learner's and parent's own "what would it take" view in the apps, and
  any `/api/v2` endpoint or OpenAPI change for career profiles.
- Earlier, age-appropriate career exploration for younger learners (from about
  Grade 4).
- A platform-curated (HQ) starter catalogue that schools could copy.
- Comparing released marks against a profile's sourced subject minimums (the
  existing programme projection already does this for university programmes).
- Trades, the arts and starting a business as separate route types.
- Translations of the existing programme and guide editor labels, which were
  missing before this step, and fluent review of the new Afrikaans and isiZulu
  strings.
