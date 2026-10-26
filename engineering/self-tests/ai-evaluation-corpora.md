# Evaluation corpus/scorer qualification

The Teaching Assistant corpus has nine explicit cases; the shared red-team
corpus has eleven, spanning the required attacks and benign controls. Existing
Tutor, Exam Coach, school assistant and message-translation corpora are retained.

Executed locally on PHP 8.3.36: **37 tests / 918 assertions pass**, without an
application database or provider call. The tests discover registered purposes,
check bounded unique inputs/canaries, score explicitly synthetic clean labels,
and independently regress refusal, subject, safety and leakage for every purpose
and the shared corpus. Every regression returns a failed score and nonzero actual
command status; the HTTP factory rejects stray requests. Disabled inference and
unqualified provider settings stay unchanged.

Removing the Teaching Assistant file reproduces the original missing-corpus gap:
the new registry-discovered CI case fails. Restoring it makes the entire cohort
pass. Owned PHP formatting and whitespace checks pass. Normal PHPUnit discovery
includes these cases without a separate paid or network-enabled workflow.

Logs: `ai-evaluation-missing-corpus-red.log`,
`ai-evaluation-corpora-final.log`, `ai-evaluation-corpora-pint.log` in private
execution scratch storage. No synthetic result is recorded as real-model safety,
quality, privacy, billing, staging or production acceptance.
