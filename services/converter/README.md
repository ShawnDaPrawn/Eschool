# NavigateEd private converter

Python/MarkItDown document conversion and offline audio/video transcription.
Laravel's queued content service supplies a source checksum and a short-lived
signed source capability. The service never accepts uploaded bytes or arbitrary
remote URLs. It returns a review-required draft; only the school can publish it.

Install the hash-locked dependencies in a Python 3.12 environment, install ffmpeg,
and run `python tool/download_model.py --path /private/models/base`. The downloader
uses a pinned public revision and verifies all file hashes. Set
`TRANSCRIPTION_MODEL_PATH` to that directory for transcription tests. Run `pytest`.
The model cohort explicitly skips if unavailable; CI configures it and must pass
actual audio and video checks. No provider API key or external inference call is used.

`CONVERTER_SERVICE_TOKEN` must be a distinct secret of at least 32 bytes.
`CONVERTER_SOURCE_ORIGIN` is the one trusted Laravel origin, without a path.
Plain HTTP is refused unless `CONVERTER_ALLOW_HTTP_SOURCE=true` is explicitly set
for the local internal Docker network. Every conversion requires the service
Bearer token; health reveals only `ok`. Requests and parser errors are not logged.
Audio/video use `/transcribe`, called through the Laravel AiGateway boundary;
ordinary `/convert` refuses media. Never call transcription directly from clients.

Run the image as uid 10001 with a read-only root filesystem and private bounded
`/tmp` tmpfs (192 MiB, mode 1777); its per-job directory/files are mode 0700/0600.
Use one worker. Source files are capped at 25 MiB, office expansion at 100 MiB,
Markdown at 2 MiB and media at 30 minutes. Parsing has process CPU/address-space,
file-size/descriptor and wall-time limits, stripped environment credentials and
no worker network access. HTTP fetching refuses redirects, proxies, compressed
responses, changed lengths/checksums, wrong origin/school/version/generation and
invalid/expired/duplicated capabilities. Job scratch is removed in finally.

Production ingress must be internal and IAM authenticated; do not expose a public
converter endpoint or grant allUsers invocation. A Cloud Run caller uses an IAM
identity token in X-Serverless-Authorization and the application Bearer token in
Authorization. The runtime model is already present and remains offline. Use a
separate environment secret, service account and bucket; do not reuse production
keys for local/staging. Deployment, staging budgets and acceptance are independent
of image/test completion. This change does not provision a paid service.

The multilingual base speech model is a bounded baseline requiring teacher review,
not a claim of Afrikaans/isiZulu classroom accuracy. Model/provider changes require
new hashes, the actual media cohort and language/accuracy review. Raw output is
sanitized in the Laravel publishing pipeline; it is never rendered directly.

Dependencies are pinned in requirements.in and resolved lockfiles with PyPI artifact
SHA-256 values. Regeneration uses pip-tools 7.6.2, followed by artifact-hash metadata;
install with `pip install --require-hashes -r requirements-dev.txt` and review the
resolved diff. Local experiments must not silently change the committed lockfiles.
