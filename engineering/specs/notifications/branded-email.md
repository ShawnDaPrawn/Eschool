# Branded school email, with the full message for school messages

Action Plan 2.1 (the template and content part; sending from the school's own domain is 2.2). Owner role: Backend (notifications). Builds on [private notice scopes](private-notice-scopes.md), [notification rules](../platform/notification-rules.md) and [one-click unsubscribe](../platform/email-unsubscribe.md).

School notice emails were a plain stub: the school's name, the notice title and its one-line body. They now look like the school's, and a school message arrives in full in the email, so a parent does not need the app to read it.

## Rules

- **Branding.** Every school notice email (`NotificationMail`) shows the school's crest (when uploaded), its name and its theme colour as a top bar and button. The colour is used only when it is a six-digit hex colour; anything else falls back to NavigateEd blue (`EmailContent::DEFAULT_COLOUR`). Branding is read when the email is sent, so a change applies to emails still in the queue.
- **Plain-text alternative.** Every notice email has a text part (`emails.school-notification-text`): school, title, content or notice, the link, the sender line and the unsubscribe link where there is one. Nothing in it is HTML-escaped.
- **Full content, opt-in per type.** A type gets its full content in the email only when `config/notification_types.php` marks it `full_content`. Today that is `communication.message` (school messages from the communication centre). The message's sanitised Markdown is rendered with the same renderer as the app (no images, no raw HTML, HTTPS links only).
- **Notice only, always.** Types marked `notice_only` never carry content, even if also marked `full_content`: safeguarding alerts, consent requests, wellbeing, discipline, progress, transfers, consultations, recognition, group conversations, school support, the weekly digest and fee statement reminders. Their emails keep the generic notice, as before.
- **Read at sending, for that recipient.** The content is fetched just before the email is sent, through the app reader's own check (`MessagePolicy::reader`) for that recipient, and only for the publication the notice announced. A recipient who lost access, a withdrawn message, or a different publication gets the notice only (a withdrawn message is not sent at all, as before). The message never travels in the queued job.
- **Unverified addresses** get the generic notice only, as before.

## Acceptance tests (`Communication/BrandedEmailTest`)

| Given | When | Then |
| --- | --- | --- |
| A school message to three verified guardians, school colour `#0a7c4b` and a crest | the queued emails are sent | the queued job holds no message text; the email shows the rendered message, colour, crest and sender line; the text part has the plain message, unescaped |
| Unverified guardians; a message withdrawn before sending | the job runs | the notice only, no content; nothing sent |
| Farid's message context | read for Theo, for another publication, at School B, or for another type | no content |
| Private types, even if marked `full_content` at runtime | asked whether they may carry content | never; and no type in config is both |
| Colours that are not colours | the email is branded | the default colour |

## Not in this step

- Sending from the school's own domain (2.2) and per-school SES tenants (2.3).
- Full content for types other than school messages (each needs its own privacy review).
- A school-chosen footer or reply-to address.
