# School information and My school

Complete the existing shared My school screen with school-scoped contacts,
published school documents and current-year term dates. Reuse settings and
term records; maintain one source for school names, short names, crests and
contacts across existing settings, context and the new editor.

School staff with active `setting-create` authority may edit contacts, display
name, short name, tagline, timezone and crest in the Material 3 editor. Active
membership roles are reloaded before reads/writes; cached or expired admin roles
do not grant publishing. Each write locks the school, rechecks authority and
records an audit and a transactional context-change event. Optional settings
clear to empty storage values and null API values. A crest is re-encoded through
the existing safe upload service; previous crests remain for cached clients.

The authenticated `GET /api/v2/schools/{school}/my-school` returns information
only for the request's selected school. It requires active membership; a URL
cannot select another school behind the header's back. The shared repository
sends the school's ID explicitly while preserving the client's authentication
mode. Generated DTOs decode contacts/documents/dates, and the existing view
model retains compatibility with older partial answers.

The school publishes PDF policies and schedules, up to 5 MB each, through a
clearly labelled publishing form. Files get random names under the current
school's public storage folder. The API exposes publication URLs and metadata,
never arbitrary submitted paths. Unlisting removes a document from My school;
previously shared public links remain valid. These are public school
publications, not private learner/staff files. Private configurable profile
files require their separate authenticated boundary.

Term dates come from the current year's existing term records and are edited
through the existing term page. The school editor links there instead of
maintaining a second calendar. The shared app screen already offers phone,
email, website, document and school-picker actions.

Verify the full publish/read/unlist path, nullable contact clearing, crest and
context synchronisation, wrong school/role/foreign document IDs, expired role
cache, invalid content/URLs and atomic failures. Run generated contracts,
Playwright/accessibility, shared app tests and the parent multi-school Chrome
flow. Real-device and staging/owner checks remain separately tracked.
