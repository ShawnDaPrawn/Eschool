# Sourced school assistant, human handoff and message translation

The existing gateway and ordinary communication foundations do not yet provide
a Parent assistant or a two-way Parent reply channel. This feature supplies a
small explicit school-support thread rather than widening staff-only broadcast
composition or reusing safeguarding/counselling cases.

## Authority and sources

The `ask_school` module stays off by default. A fresh approved Parent/child link,
active selected-school membership, current child enrolment and active user are
required for family operations. School-office work requires fresh school academic
management/communication authority; platform identities, impersonation and
mandatory-password-change accounts are excluded. Parent ownership and explicit
assigned staff authority are checked before decrypting any thread content.

The source registry is fixed server-side: versioned human-approved school
documents, the same own-child released marks/canonical attendance/calendar
sources already permitted to the family, and no clinical notes, incidents,
private staff drafts or another family's records. Each operation pins an exact
bounded source manifest/hash and reauthorizes those same references on replay.
Citings returned to the Parent are validated against that manifest and open a
fresh authorized source reader. Newer sources never silently replace a turn's
snapshot. Known identities are minimized before model input; this is not a
claim of universal name/PII detection. Provider activation remains separately
disabled until its privacy and safety qualification is recorded.

## Handoff and translation

A Parent explicitly chooses human handoff. A private school-support thread owns
an immutable question snapshot and bounded versioned replies; source records are
never automatically copied into the office thread. Staff assignment/reply and
Parent reply have fresh per-operation authority, exact UUID receipts and
school/account/owner checks. Generic notices contain only an opaque intent and
an existing private route. This does not grant access to clinical casework.

Translation is a read-side view of one exact authorized thread message in a
school-supported language. The original stays available and unchanged. The
feature-specific gateway policy resolves only that immutable message; it cannot
accept arbitrary message IDs from other source domains. Both Parent and assigned
staff readers use the same source checks, moderation, metering, prompt-version
and provenance ledger. No translated output is sent as a new message without an
explicit human action.

## Delivery and checks

Publish domain/policies and happy/refusal/wrong-school/role/owner/replay tests
first, followed by private typed API/SDK, office workbench and Parent/staff app
journeys. Source deletion/revocation, dispatch-time authority, retention,
quarantine and exact uncertain-write recovery are required. A disabled provider
must show a clear availability message while document reading and human handoff
remain usable; no synthetic answer is presented as real inference. Real-provider
evaluation, contracts and production approval remain separate evidence.

## Prepared inference and translation boundary

Inference uses immutable, encrypted, 15-minute review turns. The reader sees the
exact minimised text and confirms that visible names/identifiers have been
reviewed; known Parent/learner identity stripping does not claim universal PII
recognition. Each answer pins selected approved document versions plus optional
school calendar, own released results and today's canonical attendance. There is
no clinical, risk, staff-draft, question-bank or arbitrary source-class resolver.
Current source changes retire the old review rather than replacing its evidence.

Translation resolves one exact message from a Parent-owned/explicitly claimed
school-support thread. It preserves the original and never sends the translated
text as a message. A server-generated turn UUID binds gateway, moderation and
usage, avoiding client UUID collisions across readers. Responses have validated
language, bounded text and citations limited to the exact reviewed source tokens;
unsupported answers offer human handoff. These structural checks do not certify
a real provider's semantic grounding or translation quality.

## Office workbench and private update notices

`/ask-school` provides the panel office entry, including school Admin/Super Admin
accounts that cannot use a Teacher or Facilitator login. It reuses the same fresh
office authority and claim-before-body services. Queue metadata, approved document
versions, explicit replies, reasoned reassignment, closing and read-side translation
reviews have separate forms. Every write pins the current account/school and an
exact operation UUID. Validation and historical-year refusals never flash private
drafts to the session; fresh office/source authority precedes any draft reflection.
Visibility loss, BFCache restoration and a bounded foreground lease retire private
page contents and require a server reload.

`school_assistance.parent_update` and `school_assistance.office_update` contain
only generic wording, the implemented `/ask-school` destination and an opaque
recipient intent UUID. Thread/message/learner IDs and message/translated/source
text remain outside the notification payload. Open/reply/claim/reassignment/close
record recipient intents and outbox delivery inside the exact domain transaction;
replay adds nothing and rollback removes both. Unclaimed-office notices retire on
claim; assigned-office and Parent notices require current source ownership,
office/guardian authority and an unexpired conversation before counts, inbox rows
or queued transport. The shared execution-time scope rechecks a prepared query.

Notice intents expire within 30 days or the conversation's earlier expiry. The
latest 500 intents per recipient are retained; pruning does not alter the original
conversation or metering ledger. Parent actions explicitly use the selected child
and fresh account/school context. Office actions open the queue, never a body
supplied by the notification. Disabling inference leaves human conversations and
approved source readers working.

Both features default off, use the common moderation/caps/cost ledger and have
versioned prompts and unscored evaluation fixtures. Safeguarding quarantine blocks
the normal preview immediately; retention erases derived turn text and gateway
replay bodies while preserving usage costs and original school documents/messages.
The scheduled eraser also runs after module withdrawal. No private turn body is
copied into the generic operation receipt.
