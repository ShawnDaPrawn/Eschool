# Consultation clients

Status: client and opening-notice implementation locally verified; published CI,
deployed acceptance and release remain separate from this evidence.

The shared consultation reader is integrated into the learner, parent, teacher
and dedicated facilitator shells. Its entry requires an explicitly enabled
consultations module or the fresh facilitator profile capability. It does not
infer a parent vote from the selected child: participation is one person per
consultation, checked again by the server. Links accept only positive references.

Ballots render school-local opening and closing times, single/multiple choices,
candidate statements and private text answers. Successful participation clears
the draft. An unknown transport outcome prevents another submission until a
fresh participation read confirms whether it committed. Anonymous requests carry
answers only; they have no client identity, answer hash or idempotency receipt.
The device stores neither the ballot body nor answers. Public-live and closed
results display aggregate counts and explicit denominators; individual text
answers remain in the separately authorized manager panel.

Readers bind the exact school/account/token and authorization generation. A
coalesced loading/loaded context refresh recreates the reader even when the
payload is unchanged. Changed roles, guardian approval, children, module or
read-only state invalidate the previous generation. Backgrounding clears private
models and drafts; resume begins with fresh server access. A late response from
an invalidated reader cannot repopulate the screen.

## Evidence

- Focused shared cohort: 23 passed. The initial run exposed a 390-pixel pager
  overflow, corrected with wrapping controls; the final run also covers uncertain
  committed/uncommitted outcomes and aggregate results.
- New repository tests exercise cookie account/school headers, changed bearer
  tokens, late replies, answer-only submission and substituted page responses.
- Session tests exercise an identical-payload refresh and account, school, role,
  guardian, module and read-only changes.
- Both-app route/entry/deep-link/catalogue cohorts: 6 passed per app. Narrow
  source analyses report zero errors or warnings (11 existing family style infos,
  5 existing teacher style infos). The shared analysis reports zero errors or
  warnings; an initial fixture style info was corrected.
- Actual full-app Chrome passed for classic Teacher, dedicated Facilitator,
  Student and Parent. Teacher and both family profiles open the real ballot
  through notification actions; Facilitator opens it through the registered
  private inbox. Voting, exact account/school headers and withdrawal erasure are
  exercised. Facilitator startup leaves the classic auth box unopened.
- These Chrome targets are included in CI, with the explicit facilitator flavour.
- Full CI, Android/device execution, iOS compilation and production/store
  acceptance remain unverified by this client slice.

Opening notices are included in this increment; their server delivery and privacy
evidence is recorded in consultation-notices.md. This increment does not claim
Phase 4 or deployed acceptance complete.
