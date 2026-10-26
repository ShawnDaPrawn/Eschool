# Scoped digital card applications

Teacher, dedicated Facilitator, learner and Parent shells now open their actual
card screen from the menu/home. Parents see only current authorized family cards;
the dedicated staff shell displays its own identity. Legacy servers without the
new endpoint keep the entry hidden. Card readers and print/encoding controls
are separate staff work; an own-card entry grants no reader/issuance permission.

The screen displays private sanitized portrait pixels with a scoped decoder that
never enters Flutter's global ImageCache. Signed codes refresh within their
bounded lease and disappear while a delayed renewal outlives the old proof.
Elapsed monotonic time, minus the entire request duration, bounds display lifetime
without trusting a skewed device clock. Freeze clears the visible proof before
sending. Unknown freeze/upload responses retain their exact in-memory operation
for explicit retry; scope disposal erases pending portrait bytes. No card proof,
portrait or pending mutation is persisted in app caches.

Backgrounding erases card bodies, decoded pictures, codes and a private reason
dialog. Fresh authority is required on resume. A same-account authorization
refresh invalidates old readers even when Loading and an identical Loaded payload
arrive before the next frame. Late replies cannot refill the old scope.

Evidence:
- Actual phone-width Chrome passes for classic Teacher, dedicated Facilitator,
  Student and Parent. All reach the real shared card screen and private portrait;
  code/freeze and withdrawal paths pass, with exact account/school request binding.
  The Facilitator never opens the classic authBox. API answers are synthetic in
  these browser fixtures; the card API/domain has separate real MySQL evidence.
- Thirteen focused repository/widget tests cover three widths, cache-free private
  rendering, cookie/header scope, role/token withdrawal, delayed proof/renewal,
  bounded codes, unknown freeze retry and read-only behavior. The complete shared
  regression suite passes 542 tests. The additional coalesced authorization-generation test passes, and each app
  passes two disabled-route/catalogue checks.
- Shared card source analysis has no issues. Selected app source analyses have
  zero errors/warnings; existing style infos are retained. Three Chrome targets
  are wired into CI; fake-API Android smoke collection also selects these flows.

No physical device picker, secure chip/card reader, printer, store release or
production latency is claimed. Card panel/CR80 exports, real online scanner UI,
offline snapshots, physical encoding runtime and restricted Foundation classroom
entry remain subsequent card work. Phase 4 is not complete.
