# Early years tools: diary, observations and portfolios (first slice)

Action Plan 4.3. This first slice is the server and admin panel side of the
daily diary, observations and portfolios, and the early concern flag. The
family views and the developmental report format come later (see "Not in this
step").

## User stories

- As a class teacher of a young class, I fill in the day's diary for my whole
  class in one go: meals, rest, mood, toileting and a short note per child.
- As a class teacher, I record what I see a child do, as a note, a photograph
  or both, and tag it to a development area. Over the year these build the
  child's portfolio.
- As a class teacher, when an observation worries me, I raise an early concern
  that goes to the grade head as an intervention, in plain words about what I
  saw, without naming a condition.
- As the grade head or principal, I read a class's diary and the children's
  portfolios.
- As a school manager, I keep the school's list of development areas.
- As a parent, I decide whether a photograph of my child is kept, in the
  private photo gallery I already use.

## Where it lives

The panel page is **Early years** (`/early-years`), under Activities. It shows
when the school has switched on the `early_years` module (in the Learning
product module; off by default) and the person has a class, leads one, or
manages the school's development areas.

| Page | Path |
| --- | --- |
| Classes and development areas | `/early-years` |
| A class: its children and their observation counts | `/early-years/classes/{class}` |
| The class's diary for a day | `/early-years/classes/{class}/diary?date=` |
| A child's portfolio | `/early-years/learners/{learner}` |

### Which classes: the module, not the grade

The tools are guarded by the school's `early_years` module, not by grade. The
grade data has Grade R as its lowest grade (no Grade 000 or 00), and the owner's
stage decision puts early years at Grade 000 to Grade 2, which schools can move.
Guessing "Grade R or below" from the grade would leave out Grades 1 and 2 and
any pre-school class without a grade. A school switches the module on, and the
teachers of its young classes use it. When the stages per grade arrive (Action
Plan 4.1, open in another pull request), the module can also be limited to the
early years stage; nothing here depends on that work.

## Rules

### Who may do what

- **Writing** (the diary, observations, withdrawing, raising a concern) needs a
  teacher with a current, dated homeroom allocation to the learner's class in
  the current school year. Other staff roles are refused (403). A teacher of
  another class gets "not found" (404).
- **Reading** a class's diary and portfolios also allows the school's dated
  principal and the dated grade head of the class's grade. They do not write.
- **Development areas** are kept by people who manage the academic structure
  (`academic-structure-manage`) or the dated principal.
- Families, learners, platform staff and impersonation never reach the tools.
- The learner's class is their enrolment today in the current school year. A
  child who leaves the class leaves its teachers' reach.
- Everything is per school. Another school's class, learner or observation is
  not found, and nothing of it changes.

### Daily diary

- One entry per child per day: meals (ate everything, most, a little, did not
  eat), rest (slept, rested quietly, did not rest), mood (happy, settled, quiet,
  unsettled), toileting (went on their own, needed help or a reminder, nappy
  changed, had an accident) and a note of up to 500 characters. Every field is
  optional. The words describe the day; none is clinical.
- The page lists every child enrolled in the class that day. "For everyone"
  choices fill each child's blank fields when the teacher saves, so a whole
  class takes one save; a child's own choice wins.
- Not for a future day, and only within the current school year.
- Each row carries its revision. If someone saved a child's row after the page
  was opened, the whole save stops and nothing is written.
- A row left blank for a child with no entry writes nothing.

### Development areas

- Each school has its own list. A school with none starts with the six Early
  Learning and Development Areas of South Africa's National Curriculum Framework
  for children from birth to four, which also fit Grade R: Well-being; Identity
  and belonging; Communication; Exploring mathematics; Creativity; Knowledge and
  understanding of the world.
- Managers rename, reorder, add and switch areas off. Areas are never deleted.
  A switched-off area keeps its observations but cannot be chosen for new ones.
  At least one area stays on.

### Observations and the portfolio

- An observation is a note (up to 2,000 characters), a photograph, or both,
  tagged to one development area, for a day in the current school year up to
  today.
- Each new observation has an operation id: sending the same one again returns
  the same observation; the same id with different details is refused.
- The portfolio shows a child's observations grouped by development area, the
  newest first (the latest 300).
- Only the teacher who recorded an observation withdraws it. A withdrawn
  observation leaves the portfolio and its photograph leaves the gallery. An
  observation referred as an early concern cannot be withdrawn.

### Photographs and consent

Photographs reuse the private photo gallery's pipeline and consent model
([private gallery](../community/private-gallery.md)):

- The photo is scanned, reduced to clean JPEG pixels with no location or
  device data, scanned again with its thumbnail, encrypted, and stored under
  the school's private gallery prefix. The original filename is not kept.
- The gallery decides consent per photo, with a tag: a photo belongs to a
  child's record only once a verified guardian with approval rights approves
  its tag (or the learner, where the school allows self-approval). There is no
  standing "photo consent" flag on a learner.
- So an observation photograph is stored as the child's family photo with a
  **proposed** tag and no caption. The family sees it in their existing pending
  photos and approves or declines it there.
- **Refused when nobody can consent:** if no current verified guardian with
  approval rights exists (or, where the school allows it, the learner's own
  approved account), the photograph is refused before anything is stored. The
  teacher can record a note instead.
- **Hidden until consent:** staff see the photograph in the portfolio only once
  its tag is approved under the school's current rule. Until then the
  portfolio says it is waiting for the family. If the family declines or later
  removes the tag, the gallery retires the photo and erases it in its daily
  retention; the observation keeps its note.
- Photographs need the gallery module switched on; without it the teacher
  records notes, and existing photographs are hidden.
- An approved observation photograph also appears in the child's private
  gallery, like any approved family photo.

### Early concern

- From an observation, a teacher allocated to the class can raise one early
  concern. It is referred into the existing interventions flow
  ([early warning and interventions](../growth/risk-interventions.md)): an
  intervention owned from the start by a current grade head of the child's
  grade, with the referring teacher recorded as its creator, a review date, and
  "leadership" authority.
- It is non-clinical: there is no diagnosis or condition field. The form asks
  what the teacher saw and heard and why it worries them, and not to name a
  condition. The intervention text begins "Early concern from an early years
  observation. This is what the teacher saw, not a diagnosis.", with the area
  and the day.
- The words are kept only in the encrypted intervention. The early years record
  links the observation to the intervention and holds no text. The referring
  teacher does not open the intervention afterwards; the grade head does.
- It needs the early warning module switched on, the class mapped to a grade,
  and a grade head appointed for that grade. Otherwise the form explains this.

### Privacy

Diary notes and observation notes are encrypted. Refused forms go back with
their errors only: notes are never kept in the session. The audit log records
ids, dates and areas, never the notes.

## Data

| Table | What it holds |
| --- | --- |
| `early_years_development_areas` | The school's areas: name, description, order, on or off, revision. Unique name per school. |
| `early_years_diary_entries` | One child's day: learner, class, school year, date, meals, rest, mood, toileting, encrypted note, who recorded and last changed it, revision. Unique per school, learner and date. |
| `early_years_observations` | Learner, class, school year, development area, day, encrypted note, the gallery photo (if any), recorder, operation id and request hash, withdrawn when and by whom, revision. |
| `early_years_concerns` | The observation, learner, the intervention it became, and who raised it. No text. |

All four are school-owned (school id required, indexed, a foreign key; models
use `BelongsToSchool`). Photographs stay in `gallery_photos` and `photo_tags`.
`Interventions::refer` adds the referral path to the existing interventions
service; `GalleryPhotos::prepare` and `GalleryPhotos::storeForLearner` share the
gallery's scan, clean and store steps. The migration refuses to roll back while
any of the tables holds rows.

## Acceptance tests

`tests/Feature/EarlyYears/EarlyYearsToolsTest.php`:

| Test | What it proves |
| --- | --- |
| `test_a_class_teacher_fills_in_the_diary_for_the_whole_class_at_once` | Happy path: one save fills the class; "for everyone" fills only blanks; the note is encrypted; a stale revision stops the save; no future days. |
| `test_observations_build_a_portfolio_by_development_area_from_the_schools_own_list` | Happy path: South African default areas; an observation shows under its area; notes encrypted; operation ids replay or refuse; a manager adds and switches off areas; a teacher cannot. |
| `test_a_photograph_waits_for_the_familys_consent_through_the_gallery` | The photo is a scanned, private family photo with a proposed tag; staff cannot open it until the guardian approves in the gallery; removing the tag hides it again. |
| `test_a_photo_for_a_learner_nobody_can_consent_for_is_refused_and_nothing_is_kept` | A photo for a learner without anyone who can consent is refused, with no rows and no files; a guardian link without approval rights is not consent; a note alone is fine. |
| `test_an_early_concern_is_referred_to_the_grade_head_as_an_intervention` | The concern becomes a grade head's intervention with encrypted, non-diagnostic text; the grade head opens it, the teacher does not; once per observation; only a grade head; a referred observation stays. |
| `test_wrong_role_is_refused_and_nothing_is_written` | Parents, learners and non-teaching staff are refused (403) with counts unchanged; families never reach the panel; the module off closes the tools. |
| `test_wrong_school_ids_are_not_found_and_school_b_is_unchanged` | School B classes, learners and observations are not found from School A, and School B's rows are unchanged. |
| `test_only_the_class_teachers_write_and_only_the_recorder_withdraws` | Wrong owner: a teacher of another class is not found; a co-teacher writes but cannot withdraw a colleague's observation; the recorder can. |

## Not in this step

- **Family views of the diary and portfolio** (parent app and web portal). They
  need new `/api/v2` operations and a regenerated Dart client, which cannot be
  done here. Families already approve observation photographs through the
  existing gallery screens.
- **The developmental report format in report cards**: term reports built from
  observations in descriptive language.
- A teacher app screen for the diary and quick observations (also needs the API).
- Sending the class diary to parents ("one-tap daily diary") and notifications
  to the grade head when a concern is referred.
- Limiting the module to the early years stage per grade (after Action Plan 4.1).
- Editing an observation (withdraw and record again for now).
