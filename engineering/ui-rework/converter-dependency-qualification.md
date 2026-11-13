# Converter dependency audit qualification

The converter CI audit on PR 493 rejected two existing transitive pins. The production and development locks now contain `opentelemetry-api==1.45.2` for CVE-2026-126360 and `soupsieve==4.0` for CVE-2026-126190. Python package version normalization makes `4.0` equivalent to the advisory's `4.0.0`.

Both locks were regenerated on Python 3.12 with the repository's documented pip-tools 7.6.2, targeting only these packages:

```sh
pip-compile --index-url=https://pypi.org/simple --no-emit-index-url \
  --no-emit-trusted-host --generate-hashes --reuse-hashes \
  --upgrade-package opentelemetry-api==1.45.2 \
  --upgrade-package soupsieve==4.0.0 \
  --output-file=requirements.txt requirements.in
```

The same command was used with `requirements-dev.in` and `requirements-dev.txt`. The only resolved version changes are those two packages; no packages were added or removed, and direct inputs/application code were unchanged. Each changed package retains PyPI artifact SHA-256 hashes. No advisory was suppressed.

Local qualification on 13 November 2026 UTC (completed at 23:08 UTC; 14 November in SAST):

- Clean virtual environment installation using `pip install --require-hashes -r requirements-dev.txt` succeeded.
- `pip check`: no broken requirements.
- The CI-equivalent production audit with pip-audit 2.10.1, `--disable-pip --no-deps -r requirements.txt`: no known vulnerabilities found.
- Document, private-source, service and worker tests: 40 passed. This includes actual 30-page PDF, Word and slide conversion.
- The repository's pinned offline speech model was downloaded and every manifest artifact hash verified. Both actual WAV and MP4 transcription tests passed. Across the two test invocations, all 42 tests passed with no skipped tests.
- Lockfile `git diff --check` passed.

Durable local logs are under `/workspace/runtime/`: `converter-lock-production.log`, `converter-lock-development.log`, `converter-install.log`, `converter-pip-check.log`, `converter-audit.log`, `converter-tests.log`, `converter-model.log`, and `converter-media-tests.log`. The subsequent [converter CI job](https://github.com/ShawnDaPrawn/Eschool/actions/runs/43524771778/job/132227807043) on head `505b6c78e1f1951276414d5b03b3f626e2b1c5ff` completed successfully, including image build and authenticated ingress/read-only offline runtime checks. No deployment was performed.
