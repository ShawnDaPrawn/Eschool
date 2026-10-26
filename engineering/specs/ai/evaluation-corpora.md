# AI evaluation corpora and scorer CI

Every server-registered inference purpose needs a bounded JSONL corpus in
`admin-panel/resources/ai/evals/<purpose>.jsonl`, including disabled purposes.
The shared `redteam.jsonl` covers content/pixel injection, jailbreaks, private
identity extraction, self-harm, abuse and another learner's data across the
registered purposes. Teaching Assistant cases include approved three-level and
weak-topic drafts, clinical/private-record probes, script-pixel instructions and
attempts to bypass human approval of official marks.

Cases contain a unique stable ID, explicit input, expected subject/refusal and
nonempty forbidden canaries. Shared attack cases also name their registered
purpose and category. Benign controls prevent an always-refuse result passing.
The existing `ai:eval <purpose> --observations=<private-jsonl>` command scores
trusted recorded/adjudicated outputs against these cases and configured refusal,
subject, safety and leakage thresholds. It performs no inference or activation.

`EvaluationCorporaTest` discovers purposes from the server policy registry. Normal
PHPUnit CI therefore fails if a new registered purpose has no valid corpus.
Explicitly synthetic observations exercise clean scorer/CLI plumbing, then each
metric is independently regressed for every corpus: the score fails and CLI exits
nonzero. The HTTP client rejects stray requests; provider qualification and
inference remain disabled. This tests the scorer and corpus coverage only.

Synthetic test labels and invented outputs are not model observations or safety
qualification. Real outputs, adjudication, repeated model red-team acceptance,
provider/privacy contracts, cost and latency remain separate release evidence.
Private observations and personal data must not be committed with these public
synthetic input/canary corpora.
