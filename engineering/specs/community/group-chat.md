# Scoped group conversations

Each activity group has supervised conversation threads. Staff start group-visible
threads for learners, guardians or staff; a current family member can instead ask
staff in a private thread. Family replies are private to that person and assigned
staff by default. A school's explicit role-pair policy may permit sharing replies
with other current learners or guardians in the same audience. Cross-family
parent/learner mixing is not implicit. The composer explains its selected audience.

Every request names its staff, Parent or Student persona. A dual-role account does
not borrow another persona's learners or allocations. Staff need their own active,
dated group allocation (including a legitimate dated ancestor-admin cascade);
whole-school management permission alone never opens conversation bodies. Private
ownership binds both the account and its original persona.
Parents need a current linked, enrolled and approved group participant. Learners
need their own current Student membership, enrolment and approval. School,
account/password, module, platform-staff and impersonation checks precede private
metadata. Current policy is rechecked on reads, writes, replay and delivery.

Titles, immutable message text and moderation reasons are encrypted. SQL visibility
filters precede counts and text selection: families see permitted public messages
and their own private exchanges, while assigned staff supervise their group.
Removal preserves only the authorized tombstone and audit metadata. Exact UUID
operations retain metadata-only receipts; body fingerprints use a keyed hash.
Existing activity operations provide transactional metadata-only live invalidation.

Notifications contain no names, body snippets or peer counts. Fresh registered
source providers guard fanout, final delivery and inbox queries. The existing
shared group readers and staff private inbox open the authorized conversation;
no classic Teacher authentication is added to the dedicated facilitator shell.
The same source reader can later feed the centrally governed translation feature:
only a whitelisted message reference is accepted, never arbitrary supplied text.

The school can configure reciprocal staff/learner, staff/guardian and staff/staff
communication (staff/staff controls staff-only threads; allocated staff supervise
enabled family audiences), and separately opt in to learner/learner or
guardian/guardian shared replies. Policy administration requires the existing scoped chat-settings
permission and does not itself grant body access. The existing chat retention
setting bounds redaction, including when a module is disabled. Dedicated chat
implementation is separate from already-delivered group broadcasts/materials.
