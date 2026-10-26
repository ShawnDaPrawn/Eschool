# Facilitator progress-notice readiness

On integration head `1b7127b4ba37b84a8d74b4ea6f13399300b37c91`, hosted
Chrome job `121713334539` failed in the progress-notice journey with
`Bad state: No element`: Flutter's last finder evaluated an empty iterable
inside `pumpUntilFound`, before that helper could wait for a match.
The other 105 jobs passed, including the corrected Teacher Android journey.

The fixture now waits for the enabled learner dropdown containing learner 11,
selects the hit-testable exact learner option, and opens the exact author's
`ListTile`. It no longer applies `.last` to either asynchronous destination.
The author selector also distinguishes the note row from the profile header.
No application code, shared wait helper, timeout, permission or assertion was
changed.

The progress journey includes a three-second roster delay in a test-only
`HttpClientAdapter`. It forwards the original request options, stream and
cancellation future to the unchanged fake API, so the response body remains
valid. The old selectors reproduce the hosted `No element` stack with this
delay. The corrected selectors pass the same delayed response.

Actual browser qualification ran the complete
`facilitator_teaching_and_notices_browser.dart` target with
`--flavor=facilitator --dart-define=FLAVOR=facilitator`: all three progress,
discipline and teaching flows passed, exit 0. Existing no-clinical-access,
school/account headers, exact retries, single effects, withdrawal erasure and
closed legacy auth-box assertions remain intact. Selected Dart analysis,
formatting, whitespace and staged secret scanning also pass.

Local tools were Flutter 3.47.7 and the matched Chromium/ChromeDriver 154 pair;
the hosted failure used Chrome 155. The unchanged local target passed before
the deliberate transport delay; the valid delayed-response RED establishes
the race rather than claiming every environment reproduces it unaided.

Accepted evidence:

- `/workspace/scratch/fac-progress-roster-red-drive.log` — valid transport
  delay with original selectors, exit 1 and the matching finder stack.
- `/workspace/scratch/fac-progress-roster-green-drive.log` — same delay with
  corrected selectors, all three flows pass, exit 0.
- Matching `*-resources.jsonl`, `*-exit.txt`, and losslessly compressed
  `*-chromedriver.log.gz` records retain the execution details.
- `/workspace/scratch/fac-progress-roster-analysis.log`
- `/workspace/scratch/fac-progress-roster-gitleaks.log`

Earlier `fac-progress-delayed-red`/`fac-progress-green` runs used an unsupported
async route handler and are excluded from the proof. The interrupted
`fac-progress-valid-delay-red` attempt is also excluded.
