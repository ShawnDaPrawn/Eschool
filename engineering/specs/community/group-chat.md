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

The typed API also binds known app token/session purposes to the explicit persona:
a Parent credential cannot be relabelled as staff by a dual-role account. Existing
group-detail readers opt in with `chat_persona`; only those replies receive
`chat_available`. Legacy detail JSON stays unchanged. The capability is derived
from fresh group membership, module and audience policy, never management alone.

Message composers keep an immutable UUID draft only for the current foreground
lease. Background retirement discards text; an opaque operation UUID can check a
metadata-only outcome. A missing receipt means unconfirmed, and must never trigger
an automatic duplicate with a new UUID. Every recovered receipt is bound to the
same actor, school, group and persona and still-authorized thread/message.
Read results expose current post/reopen capabilities; closing/removing authorized
content remains possible in a readonly module. Retention also clears an expired
moderation reason when the message had already been removed.


The staff panel uses `/group-conversations`, distinct from the management
workbench. A dated allocation permits listing, composing, private replies,
moderation and thread state changes; management alone grants none of those body
rights. Expected account/school fields precede form validation, private failures
never flash drafts, and selected historical years refuse writes. Foreground
content has a 30-second monotonic lease with periodic source checks; background,
page retirement and failed/expired checks erase the DOM and inputs.

A message append records `group_chat.message_posted` in its database transaction;
after-commit acceleration preserves the outbox as recovery. Per-recipient
source keys make fanout idempotent. Private replies select only allocated staff
and their original author; explicit peer-sharing policy is checked again for
queued notices. Parent/Student notification list SQL restricts these new notices
to that portal persona before pagination, including dual-role accounts.
