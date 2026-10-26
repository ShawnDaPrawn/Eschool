# Peer tutoring browser source isolation — 26 October2026

Hosted CI run40327214256, job121590295787, completed375 Playwright cases but
failed the first390px Peer tutoring case before UI entry; its two later widths
were skipped by the serial suite. The uploaded trace retained the failed PHP
fixture command but did not retain that child process's exception output.

Local reproduction used a freshly migrated/seeded isolated test database:

1. The unchanged Peer fixture succeeds with the pristine Mastery graph.
2. Apply the real preceding Mastery journey's fractions→foundation prerequisite
   with `TopicGraph::save` and its current revision/source citation.
3. Run the unchanged Peer fixture again. It fails with **“The mastery match
   changed. Refresh the available pairs.”** The Peer policy correctly refuses
   the old fractions selection because the shared graph now prioritizes its
   unassessed foundation.

Peer now supplies its own synthetic subject/group/topic namespace to the same
complete sourced Mastery fixture builder. The original Mastery defaults stay
unchanged. Peer does not reset the earlier graph, alter matching rules or bypass
dated allocation, enrolment, approval, source or selected-match checks. The
synthetic pristine-reproduction session was cancelled through `PeerSessions`
before subsequent browser qualification to avoid introducing a separate
supervisor-time collision in the test database.

The first local combined run passed all three Peer widths and exposed a second
test race in the existing Mastery journey: it edited the old graph page while
the subject chooser's navigation was still pending. The trace contains no save
POST; the resulting page had an extra blank required row, so native validation
correctly blocked submission. The test now awaits the selected `subject_id`
URL before editing. Its saved-source, allocated-Teacher and private editor
assertions are unchanged.

Final verification on the combined dirty-predecessor state:

- **6/6 actual Playwright cases passed in2.6minutes**: Mastery and Peer at
 390/820/1280, in that order, using the actual Laravel framework router.
- Peer retains its scheduled cancellation, finished-session confirmation,
  exact original POST replay, one set of verified service effects and reversal
  assertions, plus axe, duplicate IDs, overflow and console checks.
- Both changed PHP fixtures pass Pint and PHP syntax checks; TypeScript and
  whitespace checks are recorded with the publication.
- No Peer/Mastery application service, API contract, permission, gate or CI
  check/allowlist was changed. No provider call or real learner record is used.

Local evidence: `peer-ci-pristine.log`, `peer-ci-after-dirty.log`,
`peer-ci-after-fix.log`, `peer-ci-browser.log` (the navigation-race trace) and
`peer-ci-browser-final.log`. The final browser exits0; its8124 server and isolated
database lease are released. Hosted CI still needs to run against this patch.
