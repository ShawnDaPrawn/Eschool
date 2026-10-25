# Gallery retention after a return

An old completed transfer must never retire photos from a later enrolment
episode. Present active membership alone cannot establish that boundary: the
return may later end, be suspended, or become unavailable with a disabled school.

Three real external-transfer / canonical-return / new-photo regressions failed
against the original cleanup: the later approved tag became removed in all three
states. The correction checks recorded later enrolment and Student membership
starts before applying a historical departure, inside school/student locks. It
retains the return episode even when it is no longer active. This protects data
without granting current gallery or file access.

The corrected MySQL gallery/transfer/inventory cohort passes **49 tests / 1,253
assertions**, including all three regressions, the original default-tag and image
erasure controls, consented transfer copies and current UTC transfer cases.
Selected Pint and whole-application Larastan pass. The return photos and both
variants remain; the original default-removed source image is still erased.
No production, device, scanner-provider or whole-gallery/phase acceptance is
claimed by this correction.
