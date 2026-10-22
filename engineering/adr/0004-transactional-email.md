# 0004. Transactional email through Amazon SES in `af-south-1`, bounces and complaints through SNS

Status: Accepted (Phase 0). The provider and sending domain are the default taken for Q0.1 in [phase-0-questions.md](../phases/phase-0-questions.md) until the product owner answers. Built by work stream S5 (feature 0.5). Source: Build Bible Phase 0 steps 4 and 5.

## Context

- The seed sends email synchronously inside the web request (`app/Services/MailService.php`, `Mail::send`) through an SMTP server whose host, user and password are rows in the `settings` table. A slow or failing SMTP server slows or fails the request that triggered it, and there is no record of what was delivered, bounced or complained about.
- Welcome emails, password resets, invitations and (from Phase 1) guardian approval requests are security-relevant: they must arrive, and a bounce must be visible to the school.
- Learner and parent data is personal information under POPIA; keeping processing in South Africa where practical is a product principle (all data in `africa-south1`).
- Phase 0 moves mail onto the queue (`app/Jobs/Platform/SendMail`) with a `notification_deliveries` row per attempt, and secrets out of `settings` rows into Secret Manager.

## Options

1. **Keep SMTP** through each school's own server. No new provider, but deliverability, authentication (SPF, DKIM, DMARC) and bounce handling are left to each school, and credentials stay per school.
2. **A US or EU email API** (SendGrid, Mailgun, Postmark). Good tooling and webhooks, but mail content and recipient addresses are processed outside South Africa.
3. **Amazon SES in `af-south-1` (Cape Town)**, with SNS notifications for bounces, complaints and deliveries. Processing stays in South Africa, Laravel ships an `ses` mailer, and the price is per message.
4. **Google Workspace SMTP relay.** Same cloud family as the rest of the platform, but daily sending limits, no structured bounce events, and it ties transactional mail to a Workspace licence.

## Decision

Option 3.

- **Mailer**: Laravel's `ses` mailer (`aws/aws-sdk-php`), selected by `MAIL_MAILER=ses`, region `af-south-1`. The mailer stays switchable, so SMTP keeps working for schools that self-host. Sending happens only in queued jobs on the worker, never in the request.
- **Credentials**: an AWS IAM user limited to `ses:SendRawEmail` and `ses:SendEmail` on the sending identity, with its access key in Secret Manager (`navigateed-ses-access-key-id`, `navigateed-ses-secret-access-key`, mounted as `AWS_ACCESS_KEY_ID` and `AWS_SECRET_ACCESS_KEY` on the worker only). Rotation every 90 days through the deploy runbook. Never in `settings` rows, the repository or an app.
- **Sending domain** `mail.navigateed.co.za` (staging: `staging-mail.navigateed.co.za`, so staging can never send as production):
  - DKIM: SES Easy DKIM, three CNAME records.
  - SPF: a custom MAIL FROM domain `bounce.mail.navigateed.co.za` with `MX 10 feedback-smtp.af-south-1.amazonses.com` and `TXT "v=spf1 include:amazonses.com -all"`.
  - DMARC: `_dmarc.mail.navigateed.co.za TXT "v=DMARC1; p=quarantine; rua=mailto:<reports mailbox>; adkim=s; aspf=s"`.
  - From address `no-reply@mail.navigateed.co.za`, with the school's name as the display name and the school's own reply-to address.
- **Events**: an SES configuration set with an SNS event destination for `Bounce`, `Complaint`, `Delivery` and `Reject`. The SNS topic has one HTTPS subscription to `POST /webhook/mail/ses` on the panel. The endpoint verifies the SNS message signature (certificate URL on `sns.af-south-1.amazonaws.com` only), confirms the subscription once, is idempotent on the SNS `MessageId`, and updates the matching `notification_deliveries` row by the SES message id stored as its provider reference. Hard bounces and complaints add the address to the SES account-level suppression list and mark the user's email as undeliverable for the school to fix.
- **Production access**: SES starts in the sandbox; the product owner requests production access for `af-south-1` once the domain is verified. `af-south-1` is an opt-in AWS region and must be enabled on the account first.

## Consequences

- A second cloud account (AWS) to own, bill and secure, for one service. The IAM user can only send from one identity, so a leaked key cannot read or change anything else.
- Deliverability and authentication are handled once, for every school, and bounces and complaints become data the panel can show.
- The webhook is a new public endpoint: it must verify signatures before acting and is covered by the same webhook tests as the payment webhooks.
- Mail now depends on the queue worker. If the worker stops, mail waits in the `jobs` table; the worker health check (`/up/worker`) and the failed-job alert in `engineering/runbooks/incident.md` cover that.
- Staging sends from its own subdomain and, while SES is in the sandbox, only to verified addresses, so staging cannot email real parents by mistake.
