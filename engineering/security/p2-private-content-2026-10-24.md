# Private content change review, 24 October 2026

Baseline: main after PR #97 (`5924d20`). This is a manual implementation review
of the private-content boundary and its supporting code, plus recorded automated
checks. It is not the independent pre-pilot security assessment. The Security
Diff Scan skill was read, but this host exposes neither its referenced preflight
resources nor scan helpers; no completed plugin scan/report is asserted.

The private parser accepts only an authenticated service request and a pinned
backend school/version/generation source capability. It refuses arbitrary origins,
credentials, redirects, path/query ambiguity, expired capabilities, changed size
or checksum, compression and over-limit streams. Laravel serves only a current
scanned live attempt and rechecks the author's current authority. App DTOs never
include source capabilities. Every content response, including multipart writes,
uses private/no-store; source responses add nosniff and sandbox.

Uploads are encrypted outside public storage; declared extensions and actual MIME
are checked, and ClamAV fails closed on outage, outdated definitions, unknown
replies and infection. The parser checks Office expansion/path/symlink/macro/DTD
boundaries before conversion, allows only standard stored/deflate ZIP codecs,
uses bounded child time/CPU/address-space/file/descriptor limits, strips service
credentials, disables worker Python internet sockets and limits ffmpeg protocols/
formats. Image deployment still needs private ingress, IAM, resource limits and a
read-only root/private tmpfs. These controls do not constitute an operating-system
sandbox proof against an unknown native parser compromise.

Converted Markdown is parsed into a safe subset and rendered without active raw
HTML, unsafe URLs or tracking images. Transcription is local through a metered
Laravel gateway, with scan preflight, bounded seconds, high-specificity guard
flags and mandatory human review. Broader Phase 3 semantic moderation and language
accuracy review are not claimed. Known no-inference failures release reservations;
unknown/interrupted attempts are conservatively settled, including reclaim before
the sweeper runs. No external model provider or monetary posting is introduced.

School/role/author/leadership and current dated group/enrolment/guardian rights
are checked before reads, writes and UUID replay, and again before committing an
external conversion. School suspension blocks queued completion. A draft cannot
change an existing publication's body/audience; publication is immutable and pinned
to current revisions/scan/fingerprint. Source replacement/retry races cannot
resurrect old work. Leadership publication of another author's work needs a reason.
Notification fanout uses bounded durable cursors and fresh reader checks at both
resolution and transport; notices contain generic text and IDs, never a transcript.

Evidence: private-content feature and sanitizer/scanner tests, automatic v2
contracts/route tenancy cases, SDK transport/lease tests, actual document/media
Python cohorts and dependency audit, plus two-width panel browser checks.
See the phase-2 self-test record for exact counts and pending container/AV CI.
Independent security testing, full-term pilot/device/staging evidence and human
translation/ASR review remain open. No deployment/production acceptance is implied.
