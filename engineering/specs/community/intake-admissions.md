# Intake and admissions

A school configures a dated intake, grade capacities, selected learner/parent
profile fields, required document categories, integer-cent office fee and a
retention period bounded by the platform limit. Public applicants need no school
account. A phone form saves drafts and resumes through an expiring emailed magic
link. Form CSRF, rate limits and a session-bound time/honeypot challenge protect writes;
private responses use no-store and no-referrer, and secrets never enter audit data.

Application answers and contacts are encrypted. The form uses the existing profile
field validator and rejects hidden/staff-only or changed definitions. Required
fields apply at submission, while incomplete drafts remain saveable. Documents
use the platform upload scanner, private encrypted storage, bounded sizes and
fresh application ownership before preview. Scanner failure refuses storage.

The office pipeline reviews submitted applications, records fee acknowledgement,
issues individual/bulk expiring offers, and keeps an ordered waiting list. Capacity
locks serialize offers and enrolment. Expiry, decline and withdrawal release a
place and offer it to the next eligible family; scheduled recovery repeats safely.
An accepted offer is enrolled atomically into an existing dated school year and
grade homeroom, with learner identity, guardian links, school roles, profile values,
house allocation and guardian invitations. New learner access stays unapproved
until a verified guardian explicitly approves through the existing identity flow.

Allocation proposes the least populated eligible homeroom and house, exposes the
choices to staff and rechecks their capacity/grade at commit. No payment provider
is activated; office acknowledgements are audited status records, not gateway
transactions. Canonical learner and guardian records follow the existing educational record
policy. Enrolled application copies, incomplete drafts and unsuccessful
applications expire on the configured schedule.

Verification includes wrong school/role/owner, changed authority before retry,
private-file scanner refusal, offer expiry/capacity/waiting-list concurrency,
enrolment rollback and stable retries, profile mapping, no learner autoapproval,
and real panel/public browser workflows at390/820/1280 widths. External phone,
mail, scanner and staging qualification stays separate from source/test evidence.

The intake retains a fingerprint of field source/type/requirements/options and
validation. It refuses changed definitions rather than reinterpret a saved answer;
restore those definitions or configure a new intake. Sensitive field IDs remain
classified on the intake even if a later field toggle changes. Staff authority is
checked on metadata before encrypted answers/contact are fetched. Pipeline counts
exclude unsubmitted and retention-due applications before pagination.

Public ownership capabilities are encrypted in the server session. The session's
CSRF token and minimum-age/honeypot challenge protect submission; resume email
secrets use URL fragments cleared on load and are consumed only by deliberate
POST. A family can start a sibling application without overwriting an earlier
saved draft. Private validation re-renders only an authorised author's bounded
fields and never flashes answers, contacts, reasons or fee references as old input.

`admissions:maintain` recovers offers every minute. The daily `--retention` pass
continues for disabled modules and suspended schools, deleting due encrypted
bodies, contact information, resume tokens and document bytes. It also streams
bounded batches of old unknown encrypted files left by enclosing rollbacks.
Accepted places do not expire after their acceptance deadline. Accepted places remain until the office enrols or withdraws them. After enrolment,
the application copy and its documents expire after the configured days; canonical
learner profiles, guardian links, invitations and educational records are retained. Office acknowledgements do not post
cash into a ledger. Provider-backed online payment is Phase 5 work.

Occupied places are retained as a non-private count on each intake grade. An
accepted offer moves into that count in the same transaction as enrolment;
retries and enclosing rollbacks cannot count it twice. Erasing the private
application after its retention period does not reopen the learner's place or
permit removing/reassigning its intake grade. The corrective migration backfills
still-retained enrolled applications and refuses to discard populated occupancy.

Selected parent identity fields are imported into a newly created guardian,
with application contact fields as defaults. A matching existing global identity
keeps its identity values, including values held by another school; school-owned
custom profile answers retain their separate school scope. Application contact
email proof never substitutes for learner guardian approval.
