# Delivery report for a school message

Action Plan 2.4, the delivery-report part. Owner role: Backend (communication). Builds on the [communication centre](centre.md) and `notification_deliveries` ([notification rules](../platform/notification-rules.md)).

Until now the sender of a school message saw two numbers: the recipients at publication and the read acknowledgements. Each email and push attempt was already recorded in `notification_deliveries`, but not tied to the message. This links each attempt to the school notice it carried and gives the sender a per-message report.

## Rules

- **Link.** `notification_deliveries.notification_id` records the school notice an email or push attempt carried (`DeliveryLog::forNotice`, set by `Notifier` around transport). Retries keep it. Older rows, and mail that is not a school notice (passwords, invitations), stay null.
- **Who sees it.** The message's author and the staff who may review it (`MessagePolicy::staff`): the same people who see its recipient and read counts today. A recipient, another teacher, and anyone at another school get 403 or 404. There is no report until the message is published (404).
- **What it shows.** Recipients at publication; how many read it; how many had no notification channel (their own choices or access; they can still read it in the app); and per channel (email, push) how many people reached each state: delivered, sent, waiting to send, bounced, marked as spam, failed, stopped before sending. Then each recipient by name, 50 a page: when they read it, whether they were notified, and their email and push state.
- **Counting.** A person counts once per channel. Each message's latest attempt counts, so a retry that succeeded is not also counted as failed. Push goes to every device, and a person counts by their best device: one reached device is enough. Delivered, bounced and spam reports come from the email provider later (`SesNotifications`).
- **Privacy.** Names only: never addresses, device tokens, provider message ids or provider error text (which can quote an address). Only rows of the current school and this message's notice count.

## Endpoints

| Surface | Path | Name |
| --- | --- | --- |
| Panel | `GET /communication-centre/messages/{message}/report` | `communication-centre.report` |
| API v2 | `GET /api/v2/communication/drafts/{message}/report` | `api.v2.communication.report` (`schoolMessageDeliveryReport` in `openapi.yaml`) |

Both are private and no-store, under the communication module. The panel links to the report from a published message in the workbench.

## Acceptance tests (`Communication/MessageDeliveryReportTest`)

| Given | When | Then |
| --- | --- | --- |
| A message to three guardians by in-app and email; one email delivered, one failed, one waiting; one guardian has read it | the author opens the report (service, panel and API) | 3 recipients, 1 read, email 1 delivered / 1 failed / 1 waiting, push 0; each guardian named with their state; no addresses or provider error on the page; the API matches its contract |
| The failed email is retried and sent | the report is read | it counts once, as sent |
| The same message | the approving reviewer, a recipient parent, another teacher, and School B ask for it | the reviewer sees it; the parent 403; the teacher 404; School B 404 |
| A draft | its author asks for the report | 404 |
| A delivery row of another school with the same notice id, and one with no notice | the report is read | neither counts |

## Not in this step

- Delivery reports for notices other than communication-centre messages (one-off notices from other modules).
- Resending to the people a message did not reach, or an export.
- The apps' screen for this report (the API is ready).
