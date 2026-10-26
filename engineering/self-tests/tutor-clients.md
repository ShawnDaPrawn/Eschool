# Tutor app verification

The Student reader is an online subject conversation with hints, submitted
working, explicit photo-crop approval, private cropped previews and bounded
prepared-upload recovery. A Parent has a separate aggregate-only repository and
screen. Its subject choices come from the existing authorized child content
catalogue; every request pins the expected account and school.

No conversation or original photo is saved into a local chat cache. Backgrounding
erases rendered bodies, draft controllers and photo buffers; opaque locators can
recover an owner's pending request through fresh server authorization. An
uncertain active request retries the original UUID and payload. Pending uploads
are checked against server receipts, and unused crops can be revoked to free
capacity. Camera capture and file picking are bounded to 4 MiB. Normalized pixels and an
explicitly approved rectangle go to the private Files endpoint; the server
stores and forwards only the cropped derivative.
Native cleanup is restricted to a canonically resolved app-temporary capture.

Recorded automated evidence:

- Final shared cohort: 19 tests pass. This includes PNG/JPEG pre-decode bounds,
  explicit crop approval, three Student widths, exact retries, consumed-draft
  clearing, background recovery, readonly controls, late revoked responses,
  school/account binding, Parent aggregate privacy and coalesced context refresh.
- Family entry/locale and canonical temporary-camera cleanup: 5 tests pass.
- Actual Chrome, Student and Parent: both flows pass. The Student selects and
  approves a streamed working crop, recovers one committed turn after its reply
  was lost without a second send, then erases/rejects data on background and
  access withdrawal. The Parent reads the selected child's curricular aggregate
  without any Student conversation call, then erases it on withdrawal.
- Shared and Family affected-source analysis: zero errors or warnings; style
  infos remain. The browser target is included in the existing Chrome CI matrix;
  the existing Family Android glob also picks it up.

The browser checks reproduced and corrected two real platform issues: encoded
ImageDescriptor dimensions are unsupported on web, and cached image eviction
could abort an in-flight browser decode. Header bounds plus the supported codec
now normalize the selection; private previews use the existing generation-bound
RawImage reader with a bounded optional edge (its old 360 default is unchanged).
The shared crop widget produces an immutable upload shape; each feature still
needs its own server media authority and may not reuse Tutor ownership grants.

External inference and the Tutor switch remain disabled. Synthetic protocol
fixtures do not certify real-model hints, subject lock, safety, visible-identifier
removal or the ten-second live response target. Physical camera/platform cache
behavior and real-provider privacy/performance qualification remain unrun.
Explicit topic selection and verified exercise outcome integration are separate
remaining Tutor work; this client increment alone does not finish all Phase 3.
