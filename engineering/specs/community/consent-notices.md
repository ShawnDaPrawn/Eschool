# Current guardian consent request notices

Publishing an activity consent request records generic in-app/email notification
intents within the same transaction. Only verified current guardians who may
attest for an approved, enrolled participant in the actual event span qualify.
No child names/IDs, wording, health fields or event details enter notice text or
transport payloads; an opaque recipient UUID binds the current source. A durable
unique notification key prevents duplicate notices for the same guardian/request.
Queued delivery and inbox count/read recheck the same authority through the
registered private-notice provider. Answering, cancellation, deadline, departure,
role/guardian/approval withdrawal and read-only/module changes retire the notice.

The existing locked consent catalogue and channel rules remain authoritative.
Notification acceleration waits for the actual outer transaction commit; retained
outbox recovery and a bounded scheduled scan reach eligible guardians verified
later. Recovery does not revive deleted notices or manufacture repeated channel
claims. Actual request content still requires the dedicated parent reader and
exact wording/signature checks. The parent app exposes a fresh permitted action
and handles the existing family-forms deep link, without new API/SDK operations.
