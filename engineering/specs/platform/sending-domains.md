# School sending domains

Action Plan 2.2. Owner role: Backend and Admin UX (school Settings).

A school's notices to families come from NavigateEd's own address today. With a sending domain, the school connects a domain it controls (for example `jacaranda.co.za` or `mail.jacaranda.co.za`), adds the DNS records Amazon SES needs, and once SES has verified the domain the school's notices arrive From **`<school name> <notices@that domain>`**. Families see their school's name and address, and mail from the school's domain passes DKIM and DMARC. Account mail (password resets, invitations, sign-in links, welcome mails with logins) keeps the platform address, so a school's DNS mistake can never stop someone signing in.

## User stories

- As a school's Super Admin, I enter my school's domain under Settings > Email and notifications > Email from your domain, and see exactly which DNS records to add.
- As that admin, I press "Check now" after adding the records, or leave it: the domain is checked automatically and marked verified.
- As a parent, a verified school's notices come from the school's own name and address.
- As the admin, I can disconnect the domain; notices go back to the NavigateEd address at once.
- As Navigate Ed, I can see on the HQ onboarding checklist (SA.6) whether each school's domain is connected and verified.

## Rules

- **Switch.** `MAIL_SENDING_DOMAINS` (config `mail.sending_domains.enabled`), off by default. While off, the page only explains that the feature is not switched on, every write is refused with that message, the poll does nothing, notices keep the platform address, the HQ step shows "later", and no SES client is built.
- **Who.** The school's Super Admin, or office staff with `setting-create` who are not a teacher, parent or learner (the rule of the school modules page). `SendingDomainRequest::authorize()` and `SendingDomains` both check it; anyone else gets 403 and nothing is written.
- **Domain.** Trimmed, lower-cased, trailing dot removed. Refused: an IP address (v4 or v6); anything with `@`, `/`, `:` or a space (a URL or an email address); fewer than two labels; a label that is empty, longer than 63 characters, has characters other than `a-z 0-9 -` or starts or ends with a hyphen; a top-level label that is not letters (or `xn--`); longer than 253 characters (or a MAIL FROM name over 253); a shared suffix such as `co.za`, `org.za`, `edu.za`, `gov.za`; and the platform's own domains, their subdomains and their parents: `navigateed.co.za` and `amazonses.com` (`MAIL_SENDING_DOMAINS_RESERVED`), the hosts of `APP_URL` and the learner portal, and the domain of the platform From address.
- **One per school, one school per domain.** A school with a domain must disconnect it first. A domain that another school has, or a subdomain or parent of one, is refused with "This domain cannot be connected. If it is your school's, ask Navigate Ed support to check it." (the message never says another school has it). Unique indexes on `school_id` and `domain` settle two requests at once.
- **Connect.** Creates the SES v2 email identity with Easy DKIM (`CreateEmailIdentity`, RSA 2048-bit, tagged `navigateed:school=<id>`), then sets the custom MAIL FROM domain `bounce.<domain>` with "use the default" on MX failure (`PutEmailIdentityMailFromAttributes`), and stores the three DKIM tokens. If SES already has an identity of that name it is never adopted (its DNS proof may be someone else's): the connect is refused and nothing is kept. If SES cannot be reached, nothing is kept (an identity this request created is deleted again).
- **DNS records shown.** Three CNAMEs `<token>._domainkey.<domain>` → `<token>.dkim.amazonses.com`; MX `bounce.<domain>` → `10 feedback-smtp.<region>.amazonses.com`; TXT `bounce.<domain>` → `"v=spf1 include:amazonses.com ~all"`; and a recommended TXT `_dmarc.<domain>` → `"v=DMARC1; p=none;"` (keep an existing DMARC record).
- **Verified.** When SES says the identity is verified for sending and its DKIM status is SUCCESS. DKIM FAILED (SES stopped looking after 72 hours) is "Not verified" with advice to disconnect and connect again; anything else is "Waiting for the DNS records" with the reason SES gives (records not found yet, DNS unreadable, mail service unreachable). The MAIL FROM status is shown separately; mail is delivered meanwhile.
- **Check now and the poll.** "Check now" asks SES about the school's own domain (`GetEmailIdentity`). `mail:check-sending-domains`, scheduled every five minutes, checks each waiting domain at most every ten minutes and each verified one once a day, across schools, each inside its own school. A verified domain SES no longer verifies goes back to waiting or not verified, and notices fall back to the platform address.
- **Sending.** `SendMail` decides the From of a `NotificationMail` when it sends it: `notices@<domain>` with the school's name, only when the delivery row's own school has a verified domain and the switch is on; otherwise the platform From address. Another school's domain is never used: the domain is read by the school the delivery row names. Mail that is not a `NotificationMail` is untouched.
- **Disconnect.** Deletes the SES identity (one SES no longer has counts as deleted), then the row. If SES cannot be reached, the domain stays connected and the admin is asked to try again.
- **Audit.** `sending_domain.connected`, `sending_domain.verified`, `sending_domain.unverified` (a verified domain that is no longer verified) and `sending_domain.disconnected`, against the school, with the domain and statuses. The poll's rows have no actor (system).

## Data

| Table | Tenancy | Holds |
| --- | --- | --- |
| `school_sending_domains` | required school-owned (unique foreign key to the school) | the domain (unique), SES identity name, DKIM tokens, MAIL FROM domain, status (`pending`, `verified`, `failed`), SES's DKIM and MAIL FROM statuses, `verified_at`, `last_checked_at`, a short `last_error` code, who connected it |

The SES v2 API returns no ARN for an identity, so the identity's name is stored (`ses_identity`). The migration's `down` refuses to drop the table while it holds rows: disconnect each domain first so its SES identity is deleted.

## Configuration

| Setting | Default | Meaning |
| --- | --- | --- |
| `MAIL_SENDING_DOMAINS` | `false` | switches the feature on |
| `MAIL_SENDING_DOMAINS_RESERVED` | `navigateed.co.za,amazonses.com` | domains no school may connect, besides the platform hosts |
| `services.ses.key`, `secret`, `region` | as for sending | the SES keys need `ses:CreateEmailIdentity`, `ses:PutEmailIdentityMailFromAttributes`, `ses:GetEmailIdentity`, `ses:DeleteEmailIdentity` and `ses:TagResource` |

`SesIdentities` is bound in the container; tests give it an `SesV2Client` with a fake handler, so no test calls AWS.

## Acceptance tests (`Platform/SendingDomainsTest`)

| Test | Given | When | Then |
| --- | --- | --- | --- |
| `test_the_super_admin_connects_a_domain_adds_the_records_checks_it_and_disconnects` | School A's Super Admin | connect ` Jacaranda-School.CO.za. `, check before and after SES verifies, disconnect | stored as `jacaranda-school.co.za` with MAIL FROM `bounce.jacaranda-school.co.za`; SES asked for Easy DKIM 2048-bit and the MAIL FROM; the page shows the three CNAMEs, MX, SPF and DMARC; first check "not verified yet" with the reason, then verified and the page names `notices@jacaranda-school.co.za`; disconnect deletes the identity and the row; three audit rows for School A by the admin |
| `test_the_scheduled_poll_verifies_waiting_domains_inside_each_school` | A and B connected; SES has verified A | run the poll now, after 11 minutes, 11 minutes later, a day later with DKIM failed | not due; both checked and A verified (audited with no actor); only B rechecked; A not verified, `unverified` audited |
| `test_school_notices_use_the_school_domain_only_once_it_is_verified` | School B verified; School A none, then waiting, then verified | send Farid an urgent notice each time; a welcome mail; switch off | platform address, platform address, `notices@jacaranda-school.co.za` with the school's name; the welcome mail keeps the platform address; switched off, the platform address |
| `test_teachers_and_parents_cannot_see_or_change_the_domain` | a teacher and a parent of School A | the page, connect, check, disconnect | 403 each; nothing written; no SES call |
| `test_another_school_cannot_see_check_disconnect_or_claim_the_domain` | School A has `mail.jacaranda-school.co.za`; School B's Super Admin | open the page; check and disconnect School A's id; connect the same domain (any case), its parent and a subdomain | the page shows nothing of A; 404, 404; each refused; A's row unchanged; no SES call; no audit for B |
| `test_domains_are_validated_strictly` | School A's Super Admin | IPs, a URL, an email address, a space, one label, `co.za`, bad hyphens, an underscore, an empty label, a numeric top level, a 64-character label, NavigateEd and SES domains, empty, too long; then a second domain | each refused with its message, nothing written, no SES call; the second domain refused |
| `test_an_identity_ses_already_has_or_an_unreachable_ses_leaves_nothing_behind` | SES already has `old-school.co.za`; then SES down | connect each; check and disconnect while SES is down | refused, no row, the old identity untouched; refused, no row, no audit; check records `ses_unreachable`; disconnect refused and the domain kept |
| `test_switched_off_the_page_only_explains_and_no_ses_client_is_built` | the switch off | the page, connect, the poll, a notice | explained; refused; "switched off"; platform address; no SES client built; HQ step "later" |
| `test_the_hq_onboarding_checklist_follows_the_domain` | the switch on | none, connected, verified | HQ step todo, started, done; School B still todo |

## Endpoints (school panel)

| Method | Path | Name |
| --- | --- | --- |
| GET | `/sending-domain` | `sending-domain.index` |
| POST | `/sending-domain` | `sending-domain.store` (10 a minute) |
| POST | `/sending-domain/{sendingDomain}/check` | `sending-domain.check` (20 a minute) |
| DELETE | `/sending-domain/{sendingDomain}` | `sending-domain.destroy` (10 a minute) |

## Not in this step

- Per-school configuration sets, separate account and bulk streams, tenant status events and VDM: built in Action Plan 2.3 ([SES streams](ses-streams.md)), which also records why `tenant_name` is not a column here.
- Telling the school's admin by notice or email when the domain becomes verified or stops being verified; they see it on the page and HQ sees it on the onboarding checklist.
- Retrying a notice from the platform address if SES refuses a school From between daily checks; the delivery is retried and recorded as failed as today.
- Bring-your-own DKIM keys, more than one domain per school, a Reply-To at the school, and an HQ page to release a leftover SES identity (support does that in the SES console).
- Any API or app change: this is panel and server only.
