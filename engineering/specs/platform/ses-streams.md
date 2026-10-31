# SES streams

Action Plan 2.3. Owner role: Backend and HQ (`/saas`). Builds on [school sending domains](sending-domains.md) (2.2).

Today every mail NavigateEd sends shares one Amazon SES reputation. A school whose newsletter list is full of old addresses raises the bounce rate for everyone, and the same reputation carries password resets and safeguarding alerts. With streams, each school sends through two SES configuration sets, one for **account and safety** mail and one for **bulk** mail, so SES measures each school and each kind of mail separately, the feedback for each is recorded against the school, and HQ sees each school's bounce and complaint rates per stream.

## Regional availability (checked 31 October 2026)

- **SES tenants exist in the SES v2 API.** The AWS SDK on this branch has `CreateTenant`, `CreateTenantResourceAssociation`, `GetTenant`, `ListTenants` and a `TenantName` parameter on `SendEmail`. The SES developer guide ("Tenants") says a tenant groups identities and configuration sets, has its own reputation and sending status (`ENABLED`, `DISABLED`, `REINSTATED`), can be paused by a reputation policy, and is named on `SendEmail` with `TenantName` or over SMTP with the `X-SES-TENANT` header. The guide documents that header for SMTP only, not for `SendRawEmail`, which is what Laravel's `ses` transport uses.
- **af-south-1 is not confirmed.** The August 2025 announcement says tenants are "available in all AWS Regions where Amazon SES is offered", but no page we could read lists the regions, and we have no AWS account access here to try it. Check it in the SES console for Cape Town before adopting tenants.
- **Decision (the lead's): build on configuration sets.** They exist in every SES region, work with the `ses` transport and SMTP through the `X-SES-CONFIGURATION-SET` header, and tenants can later be added on top of them (a tenant is associated with configuration sets, so these sets carry over).
- **`tenant_name`.** Action Plan 2.2 listed a `tenant_name` column on `school_sending_domains`; the 2.2 build left it out for this step. It is still not added: a school's SES unit is its pair of configuration sets in `mail_streams`. When tenants are adopted, the tenant name is `<prefix>-s<school id>` (the name the webhook below already recognises) and a column for it belongs on `mail_streams`.

## User stories

- As a parent, my school's password resets, sign-in links, invitations, safeguarding alerts, urgent notices and absence alerts keep arriving even if the school's newsletters bounce badly.
- As Navigate Ed operations, I see on `/saas/mail-streams` each school's two streams: whether the configuration set is set up and allowed to send, the tenant status SES last reported, and the last 30 days' mail with bounce and complaint rates, flagged at SES's review and pause levels.
- As Navigate Ed operations, I can press "Set up or check now" for one school (or the platform's own pair) instead of waiting for the hourly run.
- As Navigate Ed support, I can see the page but not change anything.

## Rules

- **Switch.** `MAIL_SES_STREAMS` (config `mail.streams.enabled`), off by default, like `mail.sending_domains.enabled`. While off: no SES client is built, `mail:ensure-streams` only says it is switched off, mail carries no configuration set (as today), the page explains it is not switched on and offers no button, and "Set up or check now" is refused with that message. The stream of each mail is still recorded on its delivery row, so the page's counts work before the switch.
- **Two streams.** `account`: every mail that is not a notice (`NotificationMail`), that is password and invitation mail, welcome mails with logins, admissions replies and platform staff invitations; and notices whose type matches `mail.streams.account_types`: `account.*`, `approval.*` (learner access approvals), `safeguarding.alert`, `comms.urgent` and `attendance.absence`. `bulk`: every other notice (school messages, notices, newsletters, digests, fees, academics and the rest). No `quiet_hours_exempt` flag exists in the catalogue yet, so the safety types are this config list.
- **Names.** `<prefix>-s<school id>-account` and `<prefix>-s<school id>-bulk` for a school, `<prefix>-platform-account` and `<prefix>-platform-bulk` for mail without a school. The prefix (`MAIL_SES_STREAMS_PREFIX`, default `ne`) keeps staging and production apart in one SES account; it is lower-cased, cut to letters, digits and hyphens and 20 characters, and `ne` when nothing is left.
- **Which set a mail uses.** `SendMail` decides when it sends: the stream from the mail, the school from the **delivery row** (never the ambient school). It names the school's set of that stream when the school's row is ready; otherwise the platform's set of the same stream when ready; otherwise none. Another school's set is never used. The set is named with the `X-SES-CONFIGURATION-SET` header, which SES honours for `SendRawEmail` (the `ses` mailer) and SMTP. The stream and the set are recorded on the delivery row (`mail_stream`, `configuration_set`) before the mail is handed to the mailer. With streams on, leave `AWS_SES_CONFIGURATION_SET` empty (the command warns when it is set): which of the transport option and the header SES prefers was not confirmed.
- **Setting up a set** (`MailStreams::ensure`, idempotent). For each stream: a `mail_streams` row (found by name; a row of that name for another school is never adopted), then in SES: `CreateConfigurationSet` with reputation metrics on, sending on, VDM options and the tags `navigateed:school` (`<id>` or `platform`) and `navigateed:stream`; if SES already has the set, `PutConfigurationSetReputationOptions` and `PutConfigurationSetVdmOptions` instead, and its sending status is never changed (a paused set stays paused). Then the event destination `navigateed-feedback` (create, or update when it exists) sending `DELIVERY`, `BOUNCE`, `COMPLAINT` and `REJECT` events to the SNS topic `POST /webhook/mail/ses` already reads. Then `GetConfigurationSet`; the row becomes ready. The first time a row is ready, `mail_stream.ready` is audited against the school.
- **Feedback topic.** `MAIL_SES_STREAMS_TOPIC_ARN`, or the first of `SES_SNS_TOPIC_ARNS` when unset. A topic the webhook does not accept is never used: the rows are marked failed (`no_event_topic`), nothing is sent to SES and mail uses no set. The command fails with a message saying so.
- **VDM.** Optimised shared delivery (`GuardianOptions.OptimizedSharedDelivery`) on for both streams. Engagement tracking (`DashboardOptions.EngagementMetrics`, opens and clicks) on for **bulk** and **off for account** (`mail.streams.engagement_tracking`), so sign-in, password and invitation links are never rewritten through a tracking domain. This differs from "VDM on" for the account stream on purpose; it is one config line to change.
- **Scheduled run.** `mail:ensure-streams`, hourly: sets up the platform pair and each pair, of every school that is not archived, that is not fully ready (at most 200 sets a run); then checks every ready set not checked for a day. `--school=<id>` sets up and checks one school now.
- **Checks.** A set SES says is paused (`SendingOptions.SendingEnabled` false) is shown paused; a change is audited (`mail_stream.paused`, `mail_stream.resumed`). A set SES no longer has is marked failed (`missing_in_ses`, audited `mail_stream.missing`) and no longer named, so mail falls back to the platform set instead of being refused. When SES cannot be reached, a ready set stays in use with `ses_unreachable` shown until a later check reaches SES, which clears it; a set that was never ready stays failed.
- **Tenant status events.** SES publishes `Sending Status Enabled` and `Sending Status Disabled` events (source `aws.ses`) to EventBridge for tenants. An EventBridge rule with our feedback SNS topic as its target brings them to `POST /webhook/mail/ses`, which already checks the SNS signature and the topic and processes each SNS message once. The tenant is read from the resource ARN `arn:aws:ses:<region>:<account>:tenant/<name>/<id>`, the status from `detail.data.record.status` (`ENABLED`, `DISABLED` or `REINSTATED`, which arrives as "Sending Status Disabled"), with `detail.data.origin` (`CUSTOMER_MANAGED` or `AWS_SES`) and `detail.data.record.cause`, as the SES guide's example shows. Only a tenant named `<prefix>-s<school id>` (or `<prefix>-platform`) is recognised; the status goes on that school's stream rows only, an older event never replaces a newer one, and each change is audited (`mail_stream.tenant_status`). Other tenants and other SES events (such as Advisor recommendations) are accepted and ignored. A status applies to its origin only (the SES guide); the page shows the latest event and its origin, not SES's combined status.
- **HQ.** `/saas/mail-streams` needs `mail.view` (Owner, Operations, Support); "Set up or check now" needs `mail.manage` (Owner, Operations), checked by `CheckMailStreamsRequest` and again by `MailStreams::ensureForHq`. The school must exist (otherwise a validation error and nothing is written). Each press is audited (`platform_mail_streams_checked`) against the school. The page shows counts only, never a person or address.
- **Rates.** Per school and stream over `mail.streams.report_days` (30) days, from `notification_deliveries` with a `mail_stream`: mail sent (statuses sent, delivered, bounced, complained), bounced and complained, and their rates. Flagged from 50 mails at SES's review level (bounces 5%, complaints 0.1%) or pause level (10%, 0.5%). Failed and suppressed mail is not counted.

## Data

| Table | Tenancy | Holds |
| --- | --- | --- |
| `mail_streams` | school-owned, `school_id` nullable (null is the platform pair) | the stream, configuration set name (unique), status (`pending`, `ready`, `failed`), what SES last said (sending, engagement tracking, optimised delivery, event destination), the last tenant status event (status, origin, cause, time), a short `last_error` code, `ready_at`, `checked_at` |
| `notification_deliveries` (new columns) | as before | `mail_stream` (`account` or `bulk`) and `configuration_set`, with an index on `mail_stream, created_at` |

Every read of `mail_streams` names the school explicitly (the delivery row's school, or the school HQ chose). The `mail_streams` migration's `down` refuses to drop the table while it holds rows.

## Configuration

| Setting | Default | Meaning |
| --- | --- | --- |
| `MAIL_SES_STREAMS` | `false` | switches streams on |
| `MAIL_SES_STREAMS_PREFIX` | `ne` | the start of every set's name |
| `MAIL_SES_STREAMS_TOPIC_ARN` | empty (first of `SES_SNS_TOPIC_ARNS`) | the feedback SNS topic the sets publish to |
| `mail.streams.account_types`, `engagement_tracking`, `report_*`, `bounce_*`, `complaint_*` | see `config/mail.php` | stream rules, VDM engagement per stream, report window and flags |
| `services.ses.key`, `secret`, `region` | as for sending | the keys need `ses:CreateConfigurationSet`, `ses:GetConfigurationSet`, `ses:PutConfigurationSetReputationOptions`, `ses:PutConfigurationSetVdmOptions`, `ses:CreateConfigurationSetEventDestination`, `ses:UpdateConfigurationSetEventDestination` and `ses:TagResource`; the SNS topic's policy must let SES publish to it |

`SesConfigurationSets` is bound in the container; tests give it an `SesV2Client` with a fake handler, so no test calls AWS.

**Before switching on (staging, then production):** confirm in the console that VDM is enabled for the account in af-south-1 (Virtual Deliverability Manager, Get started; the set options take effect with it, and whether they act while the account setting is off was not confirmed) and that its per-message charge fits the owner's US$10 a month ceiling for extra email spend; give the SES keys the permissions above; unset `AWS_SES_CONFIGURATION_SET`; then run `php artisan mail:ensure-streams`. To receive tenant status events once tenants are in use, add an EventBridge rule (source `aws.ses`, the two detail-types) targeting the feedback SNS topic.

## Acceptance tests

`Platform/SesStreamsTest`:

| Test | Given | When | Then |
| --- | --- | --- | --- |
| `test_the_poll_sets_up_an_account_and_a_bulk_set_for_the_platform_and_each_school` | streams on, Schools A and B | run `mail:ensure-streams`; again; a day later with A's bulk set paused in SES; then `--school=1` | six sets ready (`ne-platform-*`, `ne-s1-*`, `ne-s2-*`), each with reputation metrics, sending on, tags, VDM (engagement off for account, on for bulk; optimised delivery on) and the feedback destination for delivery, bounce, complaint and reject; `mail_stream.ready` audited twice per school; the second run asks SES nothing; a day later all six checked and A's bulk shown paused and audited; `--school=1` updates the existing sets, adds no rows, never unpauses |
| `test_mail_goes_through_the_stream_of_its_own_school` | all sets ready | an urgent notice and a welcome mail at A, a welcome mail with no school, school notices at A and at B (with A current), B's sets not ready, switched off | `ne-s1-account` (recorded on the row), `ne-s1-account`, `ne-platform-account`, `ne-s1-bulk`, `ne-s2-bulk`; then `ne-platform-bulk`, never A's; switched off, no set and the stream still recorded |
| `test_streams_follow_the_notification_type_and_names_are_deterministic` | | classify types; name sets | account and safety types on account, the rest on bulk, non-notice mail on account; names as above; the prefix cleaned |
| `test_hq_sees_each_schools_rates_and_checks_one_school_without_touching_another` | A: 60 bulk mails, 4 bounced; B: 10 account mails, 1 complaint | Operations opens the page; checks A; checks the platform; checks school 999 and a non-number | A's bulk 60/4 flagged "review", B's account not flagged (too few), no mixing; A's two sets only, audited for A; the platform pair; refused, nothing written, no SES call, B has no rows |
| `test_roles_without_the_ability_and_school_staff_cannot_see_or_check_streams` | Support, Sales, a school Super Admin, Content (service) | page and check | Support sees the page without the button and gets 403 on check; Sales 403 on both; the school admin is not let in; the service refuses Content with 403; no rows, no SES call, no audit |
| `test_an_unreachable_ses_a_missing_set_or_no_feedback_topic_leaves_mail_on_a_working_set` | no topic, a topic the webhook does not accept, SES down, SES back, SES down on a check, a set deleted in SES | ensure and send | the command fails; rows failed `no_event_topic` with no SES call and mail without a set; `ses_unreachable` and no set; ready, then still ready with `ses_unreachable`; `missing_in_ses` audited and mail on `ne-platform-bulk` |
| `test_a_check_that_reaches_ses_again_clears_an_earlier_unreachable_error` | School A's sets ready | the daily check while SES is down, then the next day's with SES back | the set stays ready with `ses_unreachable` shown on the page; then every A row ready with no error, the page no longer shows it, and mail still names `ne-s1-bulk` |
| `test_switched_off_no_ses_client_is_built_and_the_page_only_explains` | switched off | command, ensure, a notice, the page, check | "switched off"; nothing; no set but the stream recorded; explained without a button; refused; no SES client built; no rows |

`Platform/SesWebhookTest`:

| Test | Given | When | Then |
| --- | --- | --- | --- |
| `test_a_tenant_sending_status_event_is_recorded_on_that_schools_streams_only` | stream rows for A and B | signed SNS deliveries of EventBridge events: A's tenant disabled, the same message again, reinstated, an older enabled, another tenant, `ne-s2x`, an Advisor event, a tampered copy | A's two rows `DISABLED` with origin, cause and time, audited, B unchanged; "Already processed"; `REINSTATED` kept over the older event; ignored; ignored; ignored; 401; B never changed |

`Platform/SaasPanelTest` counts the two new `/saas` routes (38).

## Endpoints (HQ)

| Method | Path | Name | Ability |
| --- | --- | --- | --- |
| GET | `/saas/mail-streams` | `saas.mail-streams` | `mail.view` |
| POST | `/saas/mail-streams/check` | `saas.mail-streams.check` (20 a minute) | `mail.manage` |

## Not in this step

- **SES tenants themselves**: creating a tenant per school, associating its sets and verified identity, sending with `TenantName`/`X-SES-TENANT`, reputation policies and `GetReputationEntity` for the combined status. Wait until tenants are confirmed in af-south-1 and the header is confirmed for `SendRawEmail` (or the mailer moves to `SendEmail` v2).
- **Configuration set reputation events**: AWS documents no EventBridge or SNS event for a configuration set being paused, so none is handled; the daily check reads `SendingEnabled` instead.
- Pausing or resuming a stream from `/saas`, and pausing a school's bulk stream automatically from its rates (SES's own review and pause apply to the account).
- Tenant-level suppression lists; the account-wide suppression list stays.
- Turning VDM on for the account and the EventBridge rule (console steps above, with the cost check).
- The school health page (SA.10, PR #383) is not on this branch; when both land, its email section can read `MailStreams::report()`.
- A `quiet_hours_exempt` flag in the notification catalogue; the account stream's notice types are a config list for now.
- Any API or app change: this is panel and server only.
