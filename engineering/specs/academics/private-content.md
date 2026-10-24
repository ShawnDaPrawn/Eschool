# Private academic content conversion

Phase 2 extends the Laravel backend and shared academic clients. Legacy lessons
and installed v1 responses remain intact. A content item belongs to one school's
subject/grade/year and explicit teaching groups, with topic tags from a published
curriculum plan. Only currently allocated subject teachers or authorized academic
leaders can create/review/publish in those groups. Learners read only published
items for their own currently dated memberships; guardians do not acquire a
staff library. School/context/account changes clear private content state.

Uploads are private encrypted quarantine files. A queued ConvertContent job
rechecks authority, scans the exact source with ClamAV, and calls the private
Python MarkItDown service with an expiring signed source URL. It pins checksum,
item revision and generation; a stale job cannot overwrite a retry/publication.
Failed/unavailable scanning prevents publication and is actionable/retryable.
The converter has authenticated requests, no anonymous conversion ingress,
fixed source-origin/path validation, no redirects, bounded streaming/timeouts,
archive expansion/embedded-object limits and private per-job scratch removed in
finally. Markdown strips raw HTML/unsafe links/remote images before rendering.
No partial conversion or scan result is represented as published.

PDF, Word and slides use local parsers. Audio/video use a local offline speech
adapter, invoked only through a Laravel AiGateway transcription boundary with
school feature controls, moderation/review and duration metering/caps. This brings
forward only the transcription boundary needed by Phase 2; the wider Phase 3 AI
platform remains separate. No provider key is exposed, no learner media is sent to
a public model endpoint and no paid provider/provisioning is inferred. A teacher
reviews every converted draft, topic tags and visibility before explicit publish.

The panel and teacher app offer upload, progress/failure/retry, Markdown preview,
tagging and publish controls. The learner app offers a scoped library and reader;
adaptive layouts retain phone usability. Local/offline content caches must be
school/account/learner-bound and online revocation checked before sync/display. Explicit offline copies have a
server-issued lease of at most one day, revalidate on reconnect/resume and are
removed on account/school replacement or expiry; immediate revocation while a
device is disconnected cannot be promised. No generic cross-account cache is permitted. Source files never become public media URLs.

Implement service/contract/backend and shared clients as separate reviewable
slices. Tests cover actual sample conversions, signed source validation, AV
refusal/outage, malicious archives, sanitizer, all role/school/person boundaries,
revocation/retry races, job scratch cleanup, cubits/widgets and browser flows at
both widths. Record actual staging timing and physical-device/pilot evidence
separately; synthetic tests do not certify the full Phase 2 exit gate.
