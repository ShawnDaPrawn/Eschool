# Message delivery operations

The additive v2 Communication contract has 18 operations. Six school-owned
relations retain encrypted drafts/audiences and recipient reasons, author rules,
operation receipts and read acknowledgements. The migration adds
`communication-manage` only to Super Admin defaults, leaving existing custom
roles unchanged. Back up the application key and database together. Rollback
refuses retained communication rows; use the pre-change database backup.

The existing database worker processes `ProcessMessage` (default queue, three
attempts, 110-second timeout, 10/30/120-second backoff). Queue insertions use the
same transaction as the school lock, publication/recipient intent and batch
cursor. At most 100 recipients/review candidates are handled per pass. Mandatory school channels override the optional author selection. Transport
claims retain per-channel deduplication. No private message body enters the queue
or the generic notification. The scheduler enqueues `RecoverMessages` each minute
for pending reviews, due approved work and unfinished batches at active schools.

If the worker stops, restore it and inspect failed jobs before retrying. Recovery
resumes from the durable cursor. `processed_at` records intent processing, and
`notification_queued` records that at least one selected allowed channel queued;
neither means provider receipt. `sent` means all recipient intents were considered.
School notification rules, module availability, destination, current membership,
publication identity, guardian rights and withdrawal are checked before transport.
An obsolete approval-revision alert is suppressed. In-app notices remain generic.

No external provider or paid resource was provisioned for this slice. Browser
rehearsals use disposable demo data, an inactive learner, and in-app delivery only.
Actual device/provider delivery, staging load and independent security acceptance
remain separately tracked. Shared app routes are a following implementation slice;
do not claim their navigation or device verification from this panel/service work.
