# Private photo tags and personal gallery

A dedicated gallery asset is scanned, reduced to clean JPEG pixels and stored as
private ciphertext with a thumbnail. Clinical/report/profile files and existing
public event-media URLs are never accepted as gallery asset references. Only the
current family or an authorised current activity group can upload/preview a photo.
Staff can propose tags only inside their current group roster, using an approved
Phase 1 handle or explicit authorised learner choice; they cannot browse a child's
personal gallery. An approved family tag is required for the personal gallery.

The school rule requires a verified guardian by default. A school can explicitly
allow learner self-tag consent. Tightening the rule immediately hides self-approved
tags until guardian approval; no historical setting bypass is cached. Either the
learner or a current guardian can remove their own tag. A linked guardian needs
approval rights to approve, and staff/platform/impersonation permissions never
substitute for family ownership. SQL filters precede counts, paging and captions.

The personal gallery spans school years. Current group membership is needed for
new group proposals and unapproved previews, while an approved personal tag can
remain as private history. File reads recheck the active photo, rule, school/module,
identity and source capability before and after decrypting. Native clients retain
bytes only in memory for the current account/school/child foreground lease.

A completed transfer removes tags by default. A guardian can explicitly retain a
fingerprinted selection in the specific immutable transfer consent; an old retry
cannot silently change that choice. Actual internal transfer acceptance can copy
only those consented active photos into the receiving school's private family
scope, without source-group access, other tags or learner activation. External
transfers retain only a narrow existing guardian archive; they grant no ended
learner membership or generic old-school image access. No photos are placed in the
academic hand-off archive without that separate, explicit family choice.

Withdrawal or removal of the final family tag retires that asset immediately.
Daily private retention erases its encrypted original and thumbnail while keeping
minimal consent/audit metadata; a failed erasure retains its retry reference.
Cleanup also runs when the feature or school is disabled. Old unknown rollback
files are streamed in bounded batches within the exact school gallery prefix;
known originals, thumbnails, fresh files and other schools are retained.
Future-dated completed departures remove later unconsented tags only when the
actual departure takes effect, and an intervening return is a new authorised
membership episode rather than permission to apply an old departure again.
