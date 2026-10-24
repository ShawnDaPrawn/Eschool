# School terms and templates

Phase 2, feature 2.1. Owner role: Backend and Admin UX.

## User stories

School administrators manage year calendars with grade ranges, optional marks-lock
and report dates, and reusable school templates. Preparing a new year can seed its
terms from a template without switching the current year. Teachers, parents and
learners read the same canonical terms through their academic features.

The owner confirmed on 23 October 2026 that there are no installed clients using
semesters and authorized retiring that implementation instead of building a v1
bridge. This overrides the earlier compatibility requirement for semesters only.
Other legacy contracts retain their existing protections.

## Acceptance tests

Existing semester IDs and academic references survive an in-place migration to
terms; fresh install and rollback/reapply both work. Runtime consumers and bundled
apps use term names/keys/routes, with no duplicate semester table or dual writes.
A school admin can create, update and remove an unused term, assign R–12 ranges,
and save/apply a school calendar template. An in-use term cannot be removed.
Dates are within their year; overlapping date intervals are rejected only when
grade ranges also intersect. Optional lock/report dates do not block calendar
setup when omitted. Generated SDK full writes carry explicit nullable date fields
so a saved lock/report date can be cleared. Template application previews by default, refuses conflicts,
handles leap-day errors explicitly and is repeatable without duplicates.
Missing grade-band term mappings stop only the affected carry-forward rather than
silently broadening an allocation to the whole year. Wrong-role, wrong-school/year/template and deleted-record requests are refused;
expired/cached permissions cannot grant writes or replay a saved response.

## Data changes

Rename semesters to terms, retaining IDs, school ownership, year, date values and
soft-deletion metadata. Rename referencing semester_id columns to term_id and
class-session include_semesters to include_terms; keep academic record links.
The existing start_date/end_date storage convention remains shared with school
years; the new term API exposes starts_on/ends_on. Add grade_from/grade_to (0 = R,
12 = grade 12, defaults covering all grades), nullable marks_lock_on and reports_on.
No class grade is guessed and no dates or marks are silently rewritten.
Term foreign keys restrict physical deletion rather than cascade academic data.
School-owned term_templates have an indexed required school foreign key, name,
base year, bounded JSON calendar rows, timestamps and soft deletes. Rollback
restores original names and FK behaviour after removing the new fields/templates;
new metadata/templates require backup before rollback. Existing records survive.
Historical migration names/schema remain frozen; an old data migration uses its
own query instead of loading the removed runtime model.

## Endpoints

Authenticated v2 academics terms and term-templates use school.context, role.any,
learner.approved, module:academics and no-store headers. Read term calendars is
available to active members; management uses fresh term-list/create/edit/delete
permissions inherited by renaming existing permission records without losing role
or direct grants. Writes use FormRequests, transactional services and audit.
Template application accepts year ID and apply (default false). Every new endpoint
has OpenAPI scenarios and generated Dart SDK support. Removed semester routes and
payload keys are renamed in the bundled apps together; no compatibility aliases.

## Screens

Replace the old semester panel with a Material 3 school-terms page: year selector,
empty/error states, term dates/ranges, edit/remove controls and template preview /
apply. The existing year wizard reads canonical terms and can use a school template.
No new mark-capture or report-release screen is included.

## Notification types

None. Calendar edits are audited; they do not send unsolicited family messages.

## Module switch

academics, the existing always-on module.

## Security notes

Resolve every year, term and template under CurrentSchool. Never accept school_id
from a payload. Serialize calendar writes with the parent year row lock, repeat
fresh permission checks, refuse deleting referenced terms and use parameterized
queries. Public/global grade references contain no person data. Templates contain
calendar metadata only and cannot reach another school's calendar. Retire the
old controller and its raw HTML row construction. Marks-lock/report dates are
metadata for the future assessment engine, not a claim that its enforcement or
report-release workflow is implemented. Existing irregular data is preserved and
must be corrected through validated writes rather than silently discarded.

## Self-test steps

Test two-school API and panel access, grade/date overlap boundaries, optional
lock/report metadata, referenced-delete refusal, template preview/retry/leap day,
new-year seeding and isolation, existing-record migration/rollback and all renamed
academic consumers. Run PHP/static/style, OpenAPI/SDK, both apps/shared packages,
panel browser/accessibility and native/browser smoke checks in CI. Staging and
pilot acceptance remain separate and do not freeze independent development.

## Deployment

This is a coordinated schema/runtime replacement; follow
[the cutover runbook](../../runbooks/school-terms-cutover.md). Do not mix old and
new calendar revisions against one database. Back up before migration/rollback;
no live deployment is part of the merge authorization.

The retired semester controller no longer offers bulk class-data wiping. Year
switches preserve historical lesson term links instead of clearing them. Curriculum
copying/versioning remains separate from selecting a new current year.
