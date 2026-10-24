# Shared private content clients

Extend `eschool_features/academics` and the existing learner/parent and teacher
shells. Advertise `academic_content` only with usable entry points and routes.
Use the existing Material 3 shell, constrained content columns and phone layouts;
the wide editor places source text and its readable preview side by side.

Teachers choose year, teaching context, groups and published curriculum topics,
then upload a bounded private file. Progress and failures are explicit. Ready
Markdown is editable; saving, retrying, publishing and withdrawal preserve server
revision checks. An uncertain transport result retains the exact request UUID,
source bytes and body until replayed or the route closes. A conflict preserves
unsaved local text for comparison with the refreshed server version. Publication
requires an explicit review checkbox and any required leadership reason. History
is private and paged; source replacement warns before dropping unsaved body edits.

Learners read only current published group material. Parents use their selected
child. A notification can select another authorized child; it cannot request an
arbitrary learner. Library and reader responses are scoped, and the reader also
validates account, school, version, learner, UTF-8 checksum and bounded server
lease. Changing school, account or child clears private routes, including a reader
pushed above the library. A dismissed old route must not dismiss a new child's
notification route. Inactive screens hide private content and resume revalidates.
The Markdown renderer makes no remote image requests; HTTPS links without user
information open outside the authenticated client.

This slice uses route memory. The Phase 2 offline requirement is tracked as the
next slice: explicit encrypted account/school/learner-bound copies with the
server's maximum one-day lease, expiry/purge and online revocation checks. Do not
represent an online-only reader as completing that requirement.

Tests cover repository boundaries and multipart writes, late responses, immutable
retries, conflicts, revocation, human review, private navigation/lifecycle and
phone/wide widgets. Both shells run real sign-in-to-content Chrome integration
flows against synthetic API fixtures. Existing context responses change only by
adding the feature capability. Device, staging latency, human translation and
full-term pilot acceptance remain separate evidence.
