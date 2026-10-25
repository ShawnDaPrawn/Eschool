# Generic private wellbeing notifications

Record opaque per-recipient notice intents in the same transaction as the private
source event. The outbox relays an intent UUID only; source report/case IDs stay
in the school-owned intent table. Notifications contain a generic title/body and
the implemented private-area link, with no learner/reporter name, category,
urgency, text, image, private code, clinical count or clinical event kind.

Current dated incident-team members receive report updates. Independently dated
case assignees receive case/appointment updates. A named original reporter may
receive a reporter-visible school reply after fresh owner/guardian/enrolment
checks. Anonymous reports never acquire an account-targeted reporter notice;
internal staff replies never notify the reporter. New grants do not retroactively
turn past private work into public activity. Actor self-notices are suppressed
where an actor is already explicitly identified by the private source event.

Recheck active school/module, membership/password/learner approval, professional
role and exact current source assignment at fanout and final transport. Private
inbox SQL applies the same independent source authority before pagination,
unread counts and mark-read; revocation removes retained notice visibility.
Platform identities and impersonation are excluded. School rules and user
preferences govern channels; this is not an emergency response guarantee.

Dispatch is idempotent per source event/recipient/audience. Outbox recovery must
survive queue failure and source rollback must create neither intent nor notice.
Use the shared private scope/delivery-provider contracts and preserve unrelated
notification prefixes. Real push/email provider delivery remains separate
qualification; tests must use synthetic addresses and fake transports.
