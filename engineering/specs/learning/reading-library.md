# Reading library (public-domain books and reading rewards)

Action Plan 4.4. Owner role: Backend and Admin UX (HQ and school panel), Learning.

Learners get a library of classic books they can read in the app. Navigate Ed
keeps the catalogue: public-domain books from Project Gutenberg, cleared one by
one as public domain in South Africa, placed on shelves by grade. The server
fetches each book's text once from a configurable Project Gutenberg mirror,
strips the Project Gutenberg licence and name, and serves pages from its own
private copy. A learner's place in a book, the time they read, their answers to
comprehension questions and their reading badges are their own.

Naming: this is the **reading library** (module `reading`, tables `reading_*`,
namespace `App\Services\Reading`, HQ `/saas/reading`, school `/reading-library`,
API `/api/v2/reading`). It is not the platform **content library** of practice
questions and lessons (`library_*`, `/saas/library`, `App\Services\Learning\Library`).

## User stories

- As Navigate Ed Content staff, I add a book with the details of its Project
  Gutenberg record (the fields Gutendex lists: number, title, author with birth
  and death years, language, subjects), a reading level and a short summary.
- As Content staff, I fetch the text so I can read the file's original header
  (it names the translator and edition) and see the first page, before deciding.
- As Content staff, I record a rights decision on the book: public domain in
  South Africa, or not cleared, with the basis. Only a positive decision lets
  learners read it; I can see who decided what and when.
- As Content staff, I make shelves for a range of grades and put books on them
  in order, and write comprehension questions for a book, each unlocked at a
  page or at the end.
- As a school leader, I switch the Reading library module on, hide any shelf or
  book I do not want my learners to see, and choose whether reading badges are
  given.
- As a learner, I see the shelves for my grade, open a book, read it page by
  page on any device, and come back to my place. I answer a few questions about
  what I read and see the right answer after I answer.
- As a learner, I get a badge on my profile when I finish a book, and when I
  read on several days in a week, if my school switched them on.
- As a parent, teacher or another learner, I cannot see a learner's reading.

## Acceptance tests

`tests/Feature/Reading/ReadingLibraryTest.php` and `tests/Unit/Reading/ReadingTextTest.php`:

- Given HQ Content staff (two-factor passed), when they add a book, fetch it,
  clear it, put it on a Grade 4 to 6 shelf and add a question, then a Grade 5
  learner sees the shelf and reads every page; no page names Project Gutenberg
  or carries the header; the mirror was called once (the HQ fetch) and every
  later read came from the cache.
- A positive decision without the translator checked, or with an author who
  died within the South African term, is refused. Decisions record who and when.
- Wrong role: Finance or a Content reviewer gets 403 on `/saas/reading`; a
  school Super Admin gets 403 on every `/saas/reading` route; catalogue models
  refuse any write while a school is current, even from platform code.
- A book that is undecided or not cleared is not on any shelf a learner sees,
  is 404 to open, and is never fetched for a learner.
- Module off: every learner route and the school page answer 404. Stage: an
  early years learner is refused; a Grade 9 learner sees no Grade 4 to 6 shelf
  and gets 404 for its books. Parents and teachers get 403.
- Wrong school: School A hiding a book hides it for School A's learners only;
  School B's page and learners are unaffected; the row carries School A's id. A
  School A token sent with School B's id is refused.
- Wrong owner: Ben (same class) has none of Asha's progress, answers or minutes
  and cannot answer a question unlocked for her; extra fields such as
  `learner_id` are refused (422).
- Fetch failures: no mirror, a non-https mirror, a 500, a redirect (not
  followed), `text/html`, a connection failure, missing start/end lines,
  invalid UTF-8 and an oversized body each fail with their reason, leave no
  file, and make a learner's page 503 with `Retry-After`; the mirror is not
  asked again for 300 seconds, then a good answer is cached.
- Badges: off by default; switched on by the school, finishing a book gives
  one badge once, a second reading day in the week gives one week badge once;
  both are award presentations with source `badge` in the learner's school;
  nothing is given while the awards module is off.

## Data changes

Migration `2026_11_07_120000_create_reading_library.php`; `down()` refuses while
any reading table holds rows.

Platform-owned (global, in `SchoolOwnedTables::GLOBAL` with reasons; models use
`PlatformReadingRow`, which refuses writes while a school is current):

| Table | Holds |
| --- | --- |
| `reading_books` | Gutenberg number (unique), title, author, author birth/death years, language, subjects (JSON), reading level, summary, `rights_status` (`undecided`, `public_domain_za`, `not_cleared`), current `rights_decision_id`, revision, created_by |
| `reading_rights_decisions` | every decision: book, decision, author death year used, basis, translator checked, decided_by, decided_at. Never changed |
| `reading_book_texts` | the one cached text per book: state (`missing`, `ready`, `failed`), private path, SHA-256, bytes, pages, words, lines removed, fetched_at, last error and when. Written only by `ReadingFetcher` (also during a learner's first read), so it has no school-write guard; it holds no school or learner data |
| `reading_shelves` | name, description, grade range (grade sort order R = 0 to 12), active, revision |
| `reading_shelf_books` | shelf, book, position |
| `reading_questions` | book, `after_page` (null = end of book), prompt, 2 to 5 options, the right option, explanation, active |

School-owned (`SchoolOwnedTables::REQUIRED`, `BelongsToSchool`):

| Table | Holds |
| --- | --- |
| `reading_hidden_items` | a shelf or book the school hides (unique per school, kind and item), hidden_by |
| `reading_settings` | the two badge switches (off), days needed (2 to 7, default 4), switched_on_by (the presenter), revision |
| `reading_progress` | per learner and book: page, furthest page, seconds read, finished_at |
| `reading_days` | per learner and school-local day: seconds read |
| `reading_answers` | per learner and question: choice, correct (first answer stands) |

The text, page offsets and header live on the `reading_private` disk
(`storage/app/private/reading/books/{book}/{sha256}-text.txt`, `-pages.json`,
`-header.txt`), never in a public path. Re-fetching a book writes new files and
removes the old ones.

## Legal notes (need legal review before launch)

**South African copyright.** The Copyright Act 98 of 1978, section 3(2)(a),
gives a literary work the author's life plus 50 years from the end of the year
of death. We encode only that rule (`ReadingCatalogue::termEnded`: public domain
from 1 January of death year + 51) and refuse to clear a book whose known death
year is inside it. The rule is recorded as **needing confirmation by our legal
adviser**; it is not the whole law: anonymous and pseudonymous works,
posthumous publication, works first published elsewhere, Crown and
international-organisation works, and the separate copyright of translations,
editions and illustrations all differ. So every positive decision also needs a
written basis and a tick that the translator, editor and illustrator of the
edition were checked, and is stored with who and when. Project Gutenberg's
"public domain in the USA" label is not a South African clearance. A book is
served only while its current decision is `public_domain_za`; changing its
Gutenberg number or the author's death year sets it back to undecided.

**Project Gutenberg licence and trademark.** The Project Gutenberg licence
allows anyone to use a public-domain eBook freely once the licence and all
references to Project Gutenberg are removed; the "Project Gutenberg" name is a
trademark and may not otherwise be used with a redistributed text. So
`ReadingText`:

- cuts everything before the `*** START OF THE/THIS PROJECT GUTENBERG EBOOK`
  line and from the `*** END OF ...` line (header, licence, footer);
- drops any remaining line in the body that names Project Gutenberg,
  `Gutenberg-tm` or `gutenberg.org` (counted on the HQ page);
- refuses a file without both lines, because the licence cannot then be
  removed reliably.

Learner screens and the API never show the Project Gutenberg name or logo, and
the product is not branded with it. HQ pages name it only internally. Whether
we may credit Project Gutenberg as a source anywhere (for example an "About our
books" page) is left to legal review. The original header is kept for HQ's
rights check only.

## Fetching

`config/reading.php` (no host is built in):

| Setting | Env | Default |
| --- | --- | --- |
| `mirror_url` | `READING_MIRROR_URL` | none: nothing is fetched until set |
| `text_path` | `READING_MIRROR_TEXT_PATH` | `cache/epub/{id}/pg{id}.txt` |
| `allow_http` | `READING_MIRROR_ALLOW_HTTP` | false (https only) |
| `connect_timeout`, `timeout` | | 5 s, 30 s |
| `max_bytes` | | 8 MB |
| `content_types` | | `text/plain` |
| `retry_after_seconds` | | 300 |

`ReadingFetcher::ensure()` runs on the server, under a per-book cache lock, the
first time anyone (HQ or a learner) needs the text: the origin must be a plain
https origin without credentials, query or fragment; the path is built from the
configured template and the book's number only; redirects are not followed; a
non-2xx answer, another content type, a declared or actual size over the limit,
invalid UTF-8 or missing start/end lines each fail with a short reason stored
on `reading_book_texts`. After a failure the mirror is not asked again for that
book for `retry_after_seconds`, and learners get 503 with `Retry-After`. HQ can
fetch again at any time ("Fetch the text again"). The learner's device never
contacts the mirror. The network is blocked in development; every test uses
`Http::fake`.

## Reader

The server cuts the cleaned text into pages of whole paragraphs, about 2,500
characters each (a longer paragraph is cut at a space; at most 5,000 pages),
stores the byte offsets, and serves one page per request. EPUB is not served in
this step: plain text first, because it can be cleaned of the licence reliably
and paged on the server for any device; EPUB (images, styling) comes later.

## Comprehension checks

v1 uses questions HQ Content staff write per book: multiple choice with 2 to 5
options, unlocked when the learner's furthest page reaches `after_page`, or
when they finish the book. The answer and explanation are never sent before the
learner answers; marking is on the server; the first answer stands. There are
no scores, no class lists and no comparisons.

AI-written checks under the tutor's feature policy are a disabled seam
(`ReadingAiChecks::available()` is false, with no setting to change it, and the
API says `ai_checks: false`) until the AI provider is qualified; they would ask
only about the public-domain text and send nothing about the learner.

## Rewards

Reading badges reuse the persistence badge path (Action Plan 4.6): an award
presentation with source `badge` in the awards module, with the
`recognition_badges` ledger making each one once only, even after a manager
withdraws it. Kinds `reading_finished` (once per learner and book) and
`reading_days` (once per learner and school-local week, when the learner has
read at least a minute on `days_required` different days). Both are off until
a school leader switches them on in `/reading-library`; the leader who switches
them on is the presenter, as for persistence badges. Nothing is given while the
awards module is off or the learner has no current placement. Badges are about
the learner's own reading only: no rankings, leaderboards or comparisons.

Reading time is what the app reports (`seconds` since the last save), counted
at most 900 seconds per save and six hours per school-local day. It is a
self-reported, low-stakes figure; the badges do not depend on proving reading.

## Endpoints

API v2, all under `PrivateDomainResponse`, `auth:sanctum`, `school.context`,
`role.any`, `learner.approved`, `module:reading`, `cache.headers:private;no_store`,
and `ReadingAccess::learner()` (a current, guardian-approved Student only; never
across schools, impersonating, platform staff, a parent, a teacher or a mixed
role). A book is visible only when cleared, on an active shelf for the learner's
grade that the school has not hidden, and not hidden itself; otherwise 404.

| Method | Path | Operation |
| --- | --- | --- |
| GET | `/reading/shelves` | `getReadingShelves`: shelves, books with own progress, own week (`minutes_this_week`, `days_this_week`, `books_finished`), `ai_checks` |
| GET | `/reading/books/{book}` | `getReadingBook`: details, own progress, `checks` (total, open, answered) |
| GET | `/reading/books/{book}/pages/{page}` | `getReadingPage`: fetches once, then serves from the cache; 503 + `Retry-After` while not ready |
| PUT | `/reading/books/{book}/progress` | `saveReadingProgress`: `{page, seconds}`; 409 until the text has been opened; returns progress, week and any badge titles |
| GET | `/reading/books/{book}/questions` | `getReadingQuestions`: unlocked questions without answers; own results for answered ones |
| POST | `/reading/questions/{question}/answer` | `answerReadingQuestion`: `{choice}`; marks it; the first answer stands |

`openapi.yaml` describes them (tag `Reading`); `OpenApiContractTest` has their
scenarios. **The Dart client (`packages/eschool_api`) must be regenerated**
(`tool/generate.sh`) before the apps use these endpoints.

Web (HQ, `platform.can:reading.manage`, two-factor): `GET /saas/reading`,
`POST /saas/reading/books`, `GET|PUT /saas/reading/books/{book}`,
`POST /saas/reading/books/{book}/rights`, `POST /saas/reading/books/{book}/fetch`,
`POST /saas/reading/books/{book}/questions`, `POST /saas/reading/questions/{question}/retire`,
`POST /saas/reading/shelves`, `GET|PUT /saas/reading/shelves/{shelf}`,
`PUT /saas/reading/shelves/{shelf}/books`.

Web (school, `module:reading`, school leaders only: Super Admin or office staff
with `setting-create`, as for school stages): `GET /reading-library`,
`POST /reading-library/hidden`, `PUT /reading-library/settings`.

## Screens

- HQ `/saas/reading`: books (rights, text state, pages), add a book; shelves,
  add a shelf. A book page: rights decision history and form, text state, the
  last fetch error in words, the original header and the first page as learners
  see it, questions (retire), details. A shelf page: books in order (by catalogue
  number), details. A note when no mirror is configured.
- School `/reading-library` (in the panel menu next to Early years): each shelf with its
  cleared books, hide or show each; reading badge switches; a note when the
  awards module is off.
- Apps: **not in this step.** The learner app and its web build (the learner
  web portal is the Flutter web build, so it has no separate server-rendered
  reader) get the shelves, the paged reader, questions and progress later, on
  this API.

## Notification types

None. A badge sends the awards module's existing notices.

## Module switch

`reading` in `config/modules.php`: off by default, depends on `academics`,
stages `junior` and `high_school` (early years learners do not sign in; child
mode comes later). Part of the Learning product module. Off: every learner
route and the school page are 404, and no badge is given.

## Security notes

- School isolation: every learner row and every hidden item is school-owned and
  scoped; the catalogue holds no school or learner data; HQ actions are audited
  with no school id; school hides and badge settings are audited in the school.
- The fetcher takes no input from requests: only the book's number and the
  configured origin and template. No redirects, https only, size and type
  limits, a lock against stampedes and a back-off after failures.
- Answers are never sent before answering. Learner reading is never shown to
  parents, teachers or HQ in this step.
- Rate limits: pages 120/min, progress and answers 60/min, HQ fetch 10/min.

## Not in this step

- Flutter screens (learner app, web portal, child mode) and the regenerated Dart client.
- EPUB and images; audio; offline download of books.
- Modern and South African books (licensed content) and local languages beyond the catalogue's language field.
- AI-generated comprehension checks (seam only).
- Parent or teacher views of a learner's reading, class reading reports, and the
  reading strand of the interests mirror and the learner passport.
- Importing books in bulk from a Gutendex catalogue dump (books are added one
  by one with the same fields).

## Open questions

- Legal adviser to confirm the South African term rule and our decision
  checklist, and whether we may credit Project Gutenberg as a source anywhere.
- Which mirror to use (`READING_MIRROR_URL`), and whether to fetch from it or
  from our own copy of the files.
- Whether early years learners should get picture books through child mode.

## Self-test steps

1. As HQ Content staff, add a book, fetch it, check the header and first page,
   clear it, shelve it for Grades 4 to 6 and add a question.
2. As a school leader, switch the module on, hide and show the book, switch the
   finishing badge on.
3. As a Grade 5 learner (API), read to the last page, answer the question and
   see the badge on the profile; as another learner, see none of it.
