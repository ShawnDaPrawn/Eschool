# HQ announcements to schools

Action Plan SA.13. Owner role: Backend and Admin UX (HQ).

Navigate Ed tells schools about new releases, planned maintenance and policy changes from HQ, instead of by email from someone's inbox. An announcement goes to the Super Admins of the schools it is meant for: a notice through the school's normal notification rules, and a banner in the school panel while it is current. Each Super Admin dismisses the banner when they have read it, and HQ sees how many schools and Super Admins have. These are not the school's own announcements to its families (the `announcements` table), which stay as they are.

## User stories

- As Navigate Ed operations, I draft an announcement, see it as schools will see it and how many schools and Super Admins it would reach, then publish it.
- As Navigate Ed, I aim an announcement at every school, at schools on a package, at schools at a lifecycle stage, or at schools I choose.
- As Navigate Ed, I withdraw an announcement that was wrong, and publish a corrected one; what a school was told is never silently changed.
- As a school's Super Admin, I get a notice and see a banner while the announcement is current, dismiss it once read, and can read every announcement sent to my school on one page.
- As a school, I never see announcements meant only for other schools, which schools an announcement went to, or who read it elsewhere.

## Rules

- **Who.** The ability `announcements.manage` (Owner and Operations) reaches every HQ page and form. Sales, Finance, Content and Support do not see the link and are refused (403), and nothing is written. School accounts never reach `/saas`.
- **An announcement.** Kind (release notes, maintenance window, policy change), severity (information or warning), a title (5 to 150 characters), the text (10 to 5,000 characters), when it shows from and, optionally, until (South African time; until must be after from). A maintenance window may carry its own start and end (end after start; only for maintenance); a maintenance announcement without an "until" stops showing when its window ends.
- **Text.** Plain text with a few marks: a blank line starts a paragraph, lines starting with "- " make a list, `**bold**`, and `[words](https://link)`. Everything is escaped first, so HTML (scripts, images, other link schemes) shows exactly as typed. The banner shows the words without marks.
- **Audience.** All schools; schools on one of the chosen packages (a school with no package is on `legacy`); schools at one of the chosen lifecycle stages (a school with no HQ record is at the default stage, onboarding); or chosen schools. Only active schools are reached. A draft shows how many schools and Super Admins publishing now would reach.
- **Publishing.** Fixes the schools it reaches (one `platform_announcement_schools` row each, with how many Super Admins were told); a school that joins or changes package or stage later is not added. Each school's active Super Admins (never a platform staff account, a teacher, a parent or a learner) get a `platform.announcement` notice through `Notifier` (title; the kind and, for maintenance, the window), linking to `/navigate-ed-announcements`; delivery follows the school's notification rules. Publishing is refused when no active school matches, or when its "until" has already passed.
- **Never edited.** Only a draft changes. A published or withdrawn announcement is refused (409) on change and on a second publish; drafts carry a revision, so a form opened before another change is refused (409).
- **Withdrawing.** A published announcement can be withdrawn with a reason. The banner goes at once and the school page no longer lists it; notices already sent stay in inboxes.
- **Banner.** In the school panel, under the header, for the school's Super Admins only, while the announcement is published, its "from" has passed and its "until" has not, and until that person dismisses it. Warnings first, at most three at once. Dismissing works while a past school year is open.
- **Reads.** Dismissing (or "Mark as read" on the page) records one read per Super Admin. HQ sees, per announcement, schools reached and schools with a read, Super Admins told and reads, and a table by school. A school sees only its own people's reads (each person their own).
- **School page.** `/navigate-ed-announcements` (Settings, for Super Admins) lists every announcement published to the school and not withdrawn, newest first, with the full text and whether the person has read it. Others are refused (403).
- **Audit.** `platform_announcement_drafted`, `platform_announcement_updated`, `platform_announcement_published` (kind, severity, audience, schools and recipients counted) and `platform_announcement_withdrawn`, with ids and states, not the text. Published and withdrawn have labels in the HQ audit log (SA.3).

## Data

| Table | Tenancy | Holds |
| --- | --- | --- |
| `platform_announcements` | global | the announcement, its dates, audience (with the chosen packages, stages or school ids), status, who published or withdrew it and why |
| `platform_announcement_schools` | required school-owned (foreign key to the school) | one row per school reached, fixed at publication, with how many Super Admins were told |
| `platform_announcement_reads` | required school-owned (foreign key to the school) | one row per Super Admin who read it |

Rows are history: the migration's `down` refuses to drop tables that hold rows.

## Acceptance tests (`Platform/HqAnnouncementsTest`)

| Test | Given | When | Then |
| --- | --- | --- | --- |
| `test_operations_drafts_previews_and_publishes_to_every_schools_super_admins` | Operations; two schools, each with a Super Admin | draft for all schools; open it; publish | the preview shows the rendered text and "2 schools, 2 Super Admins"; nothing sent before publishing; then one notice per school to its Super Admin only, target rows, times stored in the app's timezone, the type offered under the school's rules, an audit row without the text |
| `test_targeting_by_package_stage_and_list_reaches_only_those_schools_super_admins` | School A: no package, no HQ record; School B: core, live | publish to core; legacy; onboarding; live; School A | each reaches only the matching school's Super Admin, never a teacher; with School B suspended, a live-only draft previews 0 and cannot publish |
| `test_school_b_never_sees_an_announcement_targeted_only_at_school_a` | one for School A, one for both | School B's Super Admin opens the page and dismisses School A's | it is not listed, no banner, 404 and no read; School A sees both, never School B's name or reads; a teacher sees no banner and is refused the page and the dismissal |
| `test_the_banner_shows_only_between_its_dates_and_goes_once_withdrawn` | maintenance from tomorrow, window on Saturday | open the panel before, during and after; withdraw | banner only between the dates (until defaults to the window end), as a warning with the window; gone once withdrawn, off the page, dismissing 404 |
| `test_dismissing_records_one_read_and_hq_sees_how_many_schools_and_admins_read_it` | published to both | School A's Super Admin dismisses twice; School B's once | one read each; the banner goes; the page shows the read; HQ shows 1 then 2 of 2 schools and Super Admins |
| `test_published_text_is_never_changed_and_stale_forms_are_refused` | a draft | stale edit and publish; withdraw a draft; publish; edit, republish; withdraw twice; edit a withdrawn one | 409s; one notice per school only; withdrawal needs a reason; the page shows the reason and no edit form |
| `test_impossible_drafts_are_refused` | Operations | until before from; a window on release notes; window ending first or half given; packages, stages, schools missing or unknown; unknown severity; publish an ended one; call the service with a bad time, and as Sales | each refused, nothing written; the service validates and checks the ability (403) itself |
| `test_only_owner_and_operations_manage_announcements_and_no_school_user_reaches_them` | a draft and a published one | Sales, Finance, Content, Support; a Super Admin of each school; a teacher, on each of the six endpoints | no link; 403 everywhere; no row, revision or notice changed |
| `test_announcement_text_is_escaped_wherever_it_is_shown` | a title and text with script, image, a javascript link, HTML in a list | render; open the banner, the school page and HQ | HTML is shown as text, only the allowed marks become markup, the javascript link stays text |

## Endpoints

| Method | Path | Name |
| --- | --- | --- |
| GET | `/saas/announcements` | `saas.announcements.index` |
| POST | `/saas/announcements` | `saas.announcements.store` |
| GET | `/saas/announcements/{announcement}` | `saas.announcements.show` |
| PUT | `/saas/announcements/{announcement}` | `saas.announcements.update` |
| POST | `/saas/announcements/{announcement}/publish` | `saas.announcements.publish` |
| POST | `/saas/announcements/{announcement}/withdraw` | `saas.announcements.withdraw` |
| GET | `/navigate-ed-announcements` | `platform-announcements.index` (school panel) |
| POST | `/navigate-ed-announcements/{announcement}/read` | `platform-announcements.read` (school panel) |

Every `/saas` route carries `platform.can:announcements.manage`; every HQ write uses `AnnouncementRequest` and the service validates and checks the ability again. The school dismissal uses `DismissAnnouncementRequest` and the service checks the Super Admin role and the school's own target row again.

## Not in this step

- Scheduling the notice for the "shows from" time: the notice goes when it is published, and the banner follows the dates.
- Sending to many schools from a queued job: publication writes each school's notice intent in one transaction, which suits today's number of schools.
- Announcements in the apps or the v2 API, and to other school roles (principal, bursar) than Super Admins.
- Translating announcements: HQ writes in English; the banner and page around them are translated.
- Deleting drafts, combining package and stage in one audience, and adding schools that join after publication.

## Time zones and paging (review fixes)

- Announcement times (`shows_from`, `shows_until`, the maintenance window, `published_at`, `withdrawn_at`, `read_at`) are stored and compared in **UTC** (`PlatformAnnouncements::STORAGE_TIMEZONE`). Inside a school, the app's time zone becomes the school's own setting (`SettingsConfigService`), so "now" must never come from `now()`. HQ enters and reads times in South African time; a school reads them in its own time zone. Test: `test_a_school_in_another_time_zone_sees_the_banner_on_the_same_clock`.
- The school's announcements page shows 25 a page, with newer and older links. Each announcement also has its own page, `GET /navigate-ed-announcements/{id}` (`platform-announcements.show`): a banner's "Read in full" opens it, and an announcement not published to the school, or not published at all, answers 404. Test: `test_every_announcement_stays_readable_a_page_at_a_time_and_on_its_own`.
