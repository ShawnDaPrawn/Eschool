# Notification acceleration commits first

The synchronous queue driver now follows the same database commit boundary as asynchronous drivers. Notifier.assign records durable intent within the caller transaction, then accelerates DispatchOutbox only outside a transaction or after its real outer commit. A later authorization refusal/rollback cannot dispatch an external notice from uncommitted state. Scheduler recovery remains unchanged.

Both focused regressions first failed on the prior condition: DispatchOutbox was queued inside the outer transaction. After the one-condition correction, the real outer rollback removes notice/outbox rows and queues nothing; a nested successful operation accelerates exactly once after the outer commit. The queue is faked to inspect dispatch timing without external delivery. Combined Notifier/private-scope cohort: 27 tests / 126 assertions pass. Full Larastan and selected Pint pass. No production delivery or provider calls were used.
