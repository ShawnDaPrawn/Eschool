# Operating private academic content

This optional Phase 2 service is separate from legacy lessons. It requires a
private converter, current ClamAV definitions and a dedicated content worker.
Absent/unavailable scanning or conversion leaves an actionable unpublished draft.
There is no automatic publication or public source download. Do not enable a
client capability before the panel/app workflow and staging checks are complete.

For a local synthetic stack, set a distinct random `CONTENT_CONVERTER_TOKEN` of
at least 32 bytes in the ignored root `.env`, then run
`docker compose --profile content up --build`. The optional containers publish no
host ports. The local stack explicitly uses HTTP on its container network;
`TRUSTED_HOSTS=web` admits its signed source requests. Production/staging must use
HTTPS and environment-specific origins/secrets/storage and private IAM ingress.
Do not provision paid capacity without respecting the owner's existing budget.

Configure Laravel `CONTENT_CONVERTER_URL`, `CONTENT_CONVERTER_TOKEN`,
`CONTENT_SOURCE_ORIGIN`, `CONTENT_CLAMAV_HOST` and optional
`CONTENT_CLAMAV_PORT` (3310). For Cloud Run enable `CONTENT_CONVERTER_IAM=true`:
the caller service account needs invocation on the internal converter; use
X-Serverless-Authorization for IAM and the separate application bearer token.
Never grant allUsers invocation. The converter uses `CONVERTER_SERVICE_TOKEN`,
`CONVERTER_SOURCE_ORIGIN`, and the hash-verified bundled offline model. A service
origin contains no path, query, credentials or fragment. A five-minute source
capability pins school/version/generation and still rechecks the live attempt,
scan and author's authority. Keep request/access logs disabled on the converter;
do not log signed URLs, documents, transcripts, Markdown or bearer/IAM values.

Run the shared PHP image with `CONTAINER_ROLE=content-worker`, or
`php artisan queue:work academic_content --queue=content --tries=3 --timeout=330`.
Its database connection has retry_after=420 seconds. The ordinary 120-second
worker does not consume this queue. Keep the standard scheduler and outbox
worker running. Uploaded events insert the dedicated job in the same transaction
as their dispatch receipt. Publication fanout uses a durable item cursor and
ID-only queued batches of at most 100 learners, avoiding a whole-school fanout
inside one outbox transaction; retries resume from that cursor. Attempts have expiring generation/attempt leases;
the every-minute recovery job settles unknown transcription reservations
conservatively and exposes an interrupted state for teacher retry. Replaying old
jobs cannot publish or overwrite a newer draft. Monitor content queue age,
failed jobs, scan/definition errors and conversion latency; `/up/worker` counts
due database jobs, including the content queue. Never use queue:retry as a way
to bypass a quarantined source or revoked author.

ClamAV is private INSTREAM, accepts only an explicit clean result and refuses
definitions older than seven days. Refresh signatures with the daemon's standard
freshclam mechanism. A detected source is quarantined; a new scan detecting an
infection in a source shared with an existing publication withdraws that item.
Fix the source and upload a new version. Scanner outages can be retried after
restoration. Parser/size/format refusal needs a corrected source. Audio/video
must have an audio stream, fit 25 MiB and fit the remaining school duration
allowance (maximum 30 minutes per attempt); oversize media is not silently
truncated. Shorten/compress the recording or change the school setting. The
multilingual model still needs teacher accuracy/language review.

Teacher review pins body, tags, scan and audience with a fingerprint and CAS
revisions. Another author's publication needs a leadership reason. Replacement
uploads and edits keep the previously published version until explicit publish;
archive removes it from new family reads. Source/history retention is deliberate;
this migration's down() refuses populated content. Roll back the application
while retaining schema/private files, or restore a verified environment backup;
do not delete source/history rows to make a migration rollback succeed.

Backups/restores include the nine new tables, private content-sources paths and
the environment encryption key. Never move production files/keys/learner data to
staging without the existing approved anonymisation/quarantine process. The
converter has no durable user-data store; per-job scratch is wiped in finally.
Run as uid 10001 with read-only root, bounded /tmp tmpfs, CPU/memory/PID limits,
one worker and no public ingress. The model is downloaded/verified at build time,
not from a learner request. Deploy/rotate the separate service secret atomically
across the caller/service, then test health, unauthenticated refusal, source
origin/expiry, real AV, conversion and unpublished review state.

CI runs real PDF (including 30 pages), DOCX, PPTX, XLSX, audio/video and malicious
archive/source tests, Python dependency audit, image build and a read-only offline
runtime smoke. PHP CI runs the actual clamd clean/EICAR integration in addition
to fake-scanner domain/race/boundary tests. A local skip of the daemon test is not
a successful real scan. No synthetic check certifies real staging p95, physical
apps, independent security review, translation quality or full-term school pilot.
