# Admin panel redesign, part one (Phase 0, feature 0.11)

Build Bible section 5.3 (the admin panel standard) and section 6, build step 11, with the Playwright checks and `DemoSchoolSeeder` of build step 13. Work stream S3. The admin panel gets navigation by job, one page pattern, a Home with a setup checklist and today's numbers, a global search, an always-visible year switcher with read-only past years, and a new-year wizard. Every existing page keeps its address and keeps working; Home, search and the wizard are built on the new page pattern.

## What changes

| Area | Before | After |
| --- | --- | --- |
| Navigation | `layouts/sidebar.blade.php`: 25 top-level entries (16 of them drop-downs) reaching 89 pages besides System Update, many named after tables (Medium, Section, Session Year, Semester) | Eight sections by job (Home, People, Academics, Communication, Money, Activities, Wellbeing, Settings) built from the registry `config/navigation.php` by `App\Services\Panel\Navigation`; groups inside each section; plain words (Learners, Homerooms, Terms, School years); an item shows only when its route exists and the person has one of its permissions |
| Layout | `layouts/master` with the header's notices overlapping the header at 1024 px | `layouts/master` with a skip link, a `<main>` landmark, the page language, notices and the read-only banner under the header, the confirm dialog, `panel.js` and `page.js`; works at 1024 px |
| Pages | Hand-built forms and lists on Bootstrap | Blade components in `resources/views/components/page/` (header, filters, table, empty state, form card, field, confirm dialog) built with the Material 3 library (`public/assets/m3`), with no Bootstrap and no bootstrap-table (`PageTable` in `page.js` draws lists from the existing endpoints), documented in `wiki/UI-Standards.md`; the shell (header, navigation drawer, banners) is built the same way and passes `BootstrapFreeTest` |
| Home | Dashboard widgets | Page header with one primary action, the setup checklist, today's numbers, then the same widgets |
| Search | A filter over the sidebar's own labels | `GET /search?q=` over learners, parents, teachers, staff, grades and homerooms, subjects and the panel's pages and settings; a combobox in the header with `/` and Ctrl/Cmd+K |
| School year | A header select whose value lived in `sessionStorage` and the session; every page saved into whatever year its form named | The header select (and the small-screen drawer's) is the switcher; any year other than the current one shows a banner, its writes are disabled in the page and refused on the server (`EnforceReadOnlyYears`), and lists follow the selected year |
| New year | Session Years page: create a year, transfers run at once, make current elsewhere | A five-step wizard over `SessionYearService` and `app/Actions/Transfers/*`; nothing is copied and the current year is untouched until the last step |
| Test data | `tests/Support/DemoSchool` for API tests only | `database/seeders/DemoSchoolSeeder.php`: one school with a user per profile, idempotent, usable on any environment |
| Browser checks | None | `admin-panel/e2e` (Playwright, axe) and a `playwright` CI job |

## User stories

- **School admin (Super Admin):** From Home I can work out, without help, how to add a learner, how to set up terms and how to send a notice. Home tells me what my school still has to set up and takes me to the page that does it. I find any learner, parent, teacher, homeroom or settings page by typing in the header. I can look at last year without changing it by accident, and I roll the school into next year in one guided page.
- **Staff with a custom role (for example Finance):** the navigation shows only the sections my role can use (Money); search finds only what I may open; Home shows only the numbers I may see.
- **Teacher:** the navigation shows only my pages (my learners, registers, lessons, marks, notices, leave); search finds only the learners of my homerooms and pages I can open; I am refused admin-only pages; the year switcher shows past years read-only.
- **Learner and parent:** nothing changes in the apps. They keep seeing the school's current year until the admin makes the next one current.
- **Engineer, Test and Review roles:** a demo school with known sign-ins exists on any environment; Playwright, with axe, checks every panel page at widths of 1280 and 1024 px.

## Acceptance tests

The scripted UX paths first, then the behaviour behind them. PHPUnit classes are in `admin-panel/tests/Feature/Panel/`, `tests/Feature/Academics/` and `tests/Feature/DemoSchoolSeederTest.php`; Playwright specs in `admin-panel/e2e/tests/`.

| Given | When | Then | Test |
| --- | --- | --- | --- |
| A new admin on Home | they look for the pages to add a learner, to set up terms and to send a notice | each is two clicks or keys away and opens in under 30 seconds | `navigation.spec.ts` |
| A brand-new school | the admin follows the Home checklist | each of the seven steps links to its page and turns done from the school's data; when all are done Home says the school is ready for learners | `HomeTest::test_a_new_school_sees_every_step_still_to_do_with_a_link_to_do_it`, `test_each_step_is_done_once_the_school_has_the_data`, `test_an_ended_year_sends_the_year_step_to_the_new_year` |
| Any learner, parent, teacher, homeroom or settings page | the admin types in the header's search box | it opens in under 10 seconds | `search.spec.ts`, `SearchTest` |
| The admin switches to last year | they look at any page | the banner shows, every list follows the year and every write button is disabled; the server refuses writes | `year-switcher.spec.ts`, `ReadOnlyYearsTest` |
| The admin runs the new-year wizard | they set up next year, pick what to copy across and switch to it | it is done in less than five minutes, and nothing in the current year changes before the switch | `new-year-wizard.spec.ts`, `NewYearTest` |
| Every page the old sidebar reached | someone with the permission looks for it | it is in the navigation, at the same address | `NavigationTest::test_every_page_of_the_old_sidebar_is_in_the_navigation`, `test_someone_with_every_permission_can_reach_every_old_sidebar_page`, `test_old_sidebar_links_still_open_their_pages` |
| A Teacher, or a custom Finance role | they open the panel | they see only the sections and items their permissions open; empty sections are hidden | `NavigationTest::test_a_teacher_sees_only_what_their_permissions_open`, `test_a_custom_staff_role_sees_only_its_own_sections`, `teacher.spec.ts` |
| Every navigation label | the panel is shown in English or Arabic | the label exists in every `resources/lang/*.json` | `NavigationTest::test_every_navigation_label_is_in_every_panel_language_file`, `TranslationsTest` |
| A page | it is opened | the current section is open, the current link has `aria-current="page"`, a skip link reaches `<main>` | `NavigationTest::test_the_page_shows_the_navigation_with_the_current_section_open`, `navigation.spec.ts` (keyboard) |
| The page components | they render | tables keep `data-escape="true"` and their empty, no-match and error states; fields are M3 text fields labelled and wired to hint and error; form cards carry the CSRF token and a draft; one M3 confirm dialog per page; no Bootstrap in the shell or the components | `PageComponentsTest`, `BootstrapFreeTest` |
| A person who can complete no checklist step (a Teacher) | they open Home | no checklist | `HomeTest::test_people_who_cannot_complete_any_step_do_not_see_the_checklist` |
| Last year selected | Home shows today's numbers | they are last year's | `HomeTest::test_todays_numbers_follow_the_selected_year` |
| The Super Admin | they search a learner's name or admission number | the learner is found and opens their report | `SearchTest::test_the_super_admin_finds_a_learner_and_opens_their_page`, `search.spec.ts` |
| A Teacher | they search a parent, a teacher, a homeroom or a settings page, or a learner outside their homerooms | nothing; their own homeroom's learners are found and open the learner list | `SearchTest::test_a_teacher_finds_nothing_they_cannot_open` |
| A learner named `<script>alert(1)</script>` | someone searches | the JSON carries no raw `<`; the results page shows the text escaped | `SearchTest::test_results_are_escaped_in_json_and_on_the_page` |
| 22 matching learners, one letter, a `%` or 500 characters | someone searches | 5 per group in the box, 20 on the page; too short or a wildcard finds nothing; long input is cut | `SearchTest::test_search_keeps_to_its_limits` |
| Last year selected | someone searches a learner of this year | not found | `SearchTest::test_learners_follow_the_selected_year` |
| A guest | they call `/search` | no data | `SearchTest::test_a_guest_finds_nothing` |
| Last year selected | someone POSTs, PUTs or DELETEs year data (JSON or a plain form) | 423 with "You are looking at …, which is read-only. Switch to … in the header to make changes." (JSON) or a redirect back with that message and the typed input; nothing is saved | `ReadOnlyYearsTest::test_a_past_year_refuses_every_write_with_a_clear_message`, `test_a_plain_form_post_in_a_past_year_goes_back_with_the_message_and_the_input` |
| Last year selected | someone switches back, signs out, checks their password or saves a year-independent setting (roles) | it works | `ReadOnlyYearsTest::test_switching_back_signing_out_and_the_profile_still_work_in_a_past_year`, `test_year_independent_settings_can_be_saved_in_a_past_year` |
| Last year selected | a list endpoint is called without `session_year_id` | it answers for last year; an explicit year still wins; `$_GET` is restored after the request | `ReadOnlyYearsTest::test_lists_follow_the_selected_year` |
| The current year | the admin creates next year in the wizard | the current year's rows, the `session_year` setting and the panel's selected year are unchanged; next year has its terms and nothing carried forward | `NewYearTest::test_creating_next_year_leaves_the_current_year_untouched` |
| Next year exists | the admin makes it current with subjects, grading and teachers chosen | exactly those are copied, the year becomes current and default, promotions take effect, the panel follows it | `NewYearTest::test_the_switch_runs_the_chosen_transfers_and_makes_the_year_current` |
| Next year already has a grading scale | the switch runs with grading chosen | grading is skipped, not duplicated | `NewYearTest::test_the_switch_never_copies_what_the_new_year_already_has` |
| An ended year, or the current year | someone tries to switch to it | 422; the current year stays | `NewYearTest::test_an_ended_or_current_year_cannot_be_switched_to` |
| Bad dates, a taken name, a late charge over 100 | the admin creates next year | 422 with one error per field, shown under each field | `NewYearTest::test_next_year_is_validated_field_by_field` |
| A Teacher | they open the wizard or call its endpoints | redirect home, or 403; nothing is created or switched | `NewYearTest::test_only_people_who_manage_years_can_use_the_wizard` |
| An installed database, or an empty one | `php artisan db:seed --class=DemoSchoolSeeder` runs, once or again | one user per profile with the documented password; nothing duplicated; a running current year is never replaced | `DemoSchoolSeederTest` |
| Every panel page, as the Super Admin, at 1280 and 1024 px | it loads | no console errors or failed requests, no horizontal scroll, no serious or critical axe violation except the listed legacy ones | `pages.spec.ts`, `allowlist.json` |
| The old System Update and GET maintenance URLs | anyone asks for them | 404 (passes once S1's removal is merged) | `removed-urls.spec.ts` |

## Data changes

None. No migration and no new table. New configuration files: `config/navigation.php` (the navigation registry) and `config/panel.php` (`read_only_years.allow`, the writes allowed while another year is shown). The year selected in the panel stays in the session key `session_year`, as before. `DemoSchoolSeeder` writes demo rows only when someone runs it.

## Endpoints

All inside the panel's `['Role', 'auth']` group:

| Method and path | Name | Permission | Answers |
| --- | --- | --- | --- |
| `GET search?q=` | `search` | any panel user; each result group needs its own permission | JSON for `Accept: application/json` (`{error, query, groups: [{key, label, items: [{type, title, subtitle, url}]}], search_url}`), the results page otherwise |
| `GET new-year` | `new-year.index` | `session-year-create` | the wizard |
| `POST new-year/year` | `new-year.store` | `session-year-create` | `{error, message, data: {id, name}, redirect}`; 422 with `errors` |
| `GET new-year/preview?year_id=&carry[]=` | `new-year.preview` | `session-year-create` | `{error, data: {carry: [...], promoted, leaving, learners}}` |
| `POST new-year/make-current` | `new-year.make-current` | `session-year-edit` | `{error, message, data: {copied, skipped}, redirect}`; 422 for an ended or current year |

Changed: `POST set-session-year` redirects back for a plain form (the banner's button); JSON callers get `{success: true}` as before. The panel group also runs `EnforceReadOnlyYears`. No API route changes; the v1 snapshots are untouched.

## Screens

- Every panel page: header (search, school year switcher, cache clear for the Super Admin, language, account), the navigation by job, notices and the read-only banner under the header.
- Home: welcome header with "Add a learner", the setup checklist (folds into one line when complete), today's numbers (learners this year, staff present today, registers taken today, notices this year), then the existing widgets.
- Search results page.
- New-year wizard: next year, carry forward, year-end checks, preview, make it current.

## Notification types

None.

## Module switch

Each navigation item names the module Phase 1 will check (`online_exams` and `chat` from Appendix B; `null` for pages that are always on). `Navigation::moduleEnabled()` returns true until Phase 1 adds `config/modules.php` and `school_modules`.

## Security notes

- Hiding an item is not a check: every page keeps its own permission check. Search checks the permission behind each group and scopes learners like the learner list (selected year, a teacher's own homerooms).
- The read-only guard denies by default: every POST, PUT, PATCH and DELETE in the panel is refused while another year is shown, except the patterns in `config/panel.php`. The page's disabled buttons are convenience; the server is the control.
- Search escapes LIKE wildcards, caps input at 100 characters, hex-escapes markup in JSON and renders with `textContent`/`safeUrl()` in the box and `{{ }}` on the page.
- The wizard's FormRequests authorize (`session-year-create`, `session-year-edit` for the switch) and validate every field; the switch runs in one transaction.
- Form drafts in `sessionStorage` never keep passwords, files or hidden fields and are cleared when the save works.
- The demo accounts are public: `DemoSchoolSeeder` must not run on a production database that real people use.

## Self-test steps

On staging, seeded with `InstallationSeeder` and `DemoSchoolSeeder` (password `NavigateDemo2026`):

1. As `admin.a@demo.navigateed.test` at 1024 px in Chrome: from Home, open People > Add a learner, Academics > Terms, Communication > Notices; time each.
2. Press `/`, type "Lerato", Enter: the learner's report opens. Press Ctrl+K, type "school profile", Enter, open the result.
3. Switch the header's school year to last year: the banner shows on Home and on Notices; the Submit button is disabled; the learner list shows last year's learners. Click "Back to …".
4. Open Academics > Start the new school year: create next year, keep the carry-forward ticks, read the checks and the preview, make it current; Home shows the new year as current. Make this year current again on School years.
5. Sign in as `teacher.a@demo.navigateed.test`: no Settings or Money sections; `/settings` sends you home.
6. Sign in as `finance.a@demo.navigateed.test`: only Home and Money.
7. On a phone width, open the drawer: the year switcher is at the top.

## Defaults taken

- Every year other than the current one is read-only, including a year being prepared; next year is set up through the wizard's carry-forward, and edited once current.
- Learner leave requests sit under Wellbeing (absence), staff leave under People; Events, Photos and Videos under Activities; the public website's content under Communication; Holidays and Promote learners under Academics > School year.
- No address changed, so no redirects were needed; System Update is not in the navigation (stream S1 removes it).
- The demo seeder adds a custom "Finance" staff role because the seed has no staff roles; the e2e server runs with `CACHE_DRIVER=array` so the per-account request limits don't throttle the page sweep.

## Deferred

- Legacy pages keep their own markup; the axe problems specific to them are listed in `admin-panel/e2e/allowlist.json` (unlabelled filter selects, icon-only row actions, page-specific contrast) and go when each page moves to the page pattern.
- Breadcrumbs show section and group only; a "recent pages" list and saved searches are not built.
- A 390 px Playwright width (the Build Bible, section 5.2) was not in this stream's task; the e2e projects cover 1280 and 1024 px.
