# SMS and WhatsApp for urgent notices

Action Plan 2.7. Owner role: Backend (notifications), with Admin UX (school settings) and HQ.

Owner's decision (31 October 2026): **Twilio for both SMS and WhatsApp**, through **one NavigateEd Twilio account for the whole platform**, never an account per school. Credentials come from platform configuration and the environment only.

SMS and WhatsApp are a third and fourth transport beside mail and push, for urgent and safety notices only. A school opts in, chooses which of those notices use each channel, and pays the price NavigateEd pays Twilio for each message (pass-through).

## User stories

- **School office (Super Admin or office staff with `setting-create`):** in Settings > SMS and WhatsApp I switch SMS, WhatsApp or both on, and choose which urgent notices use each. Both are off until I do. Before WhatsApp goes on I confirm that guardians have agreed to receive WhatsApp messages from the school.
- **Guardian:** when the school sends an urgent notice or my child is marked absent, I also get an SMS or WhatsApp message on the number the school office has for me, once I have verified that number in the app. It never names my child or gives details: it tells me there is an urgent message and to open the NavigateEd app. Only a notice the school sends to everyone carries the school's own words.
- **Guardian or staff member:** in the app I see the last three digits of the number I am texted on at this school, ask for a code, and type it to verify the number. For each notice the school texts me I choose SMS, WhatsApp, both or neither. Nobody else can verify my number or change my choice; the school sees only that I chose.
- **School office:** I can also switch SMS and WhatsApp on for staff, for urgent notices and safeguarding alerts. I see how many people verified a number and, per notice, how many made their own choice, never who chose what.
- **NavigateEd Finance:** each school's monthly invoice has a line per channel passing on what Twilio charged for the texts the school sent the month before, in rand.
- **Teacher, parent, learner:** I cannot change the school's SMS and WhatsApp setting (403).
- **NavigateEd Finance or Operations:** in HQ > SMS and WhatsApp I see, per school and month, how many SMS and WhatsApp messages were sent, their segments, how many were not delivered, and what Twilio charged, to pass on at cost.
- **Platform operator:** every message has a `notification_deliveries` row (`sms.<type>` or `whatsapp.<type>`) that Twilio's signed status callback moves to sent, delivered, failed or undelivered. No phone number, message text or credential is ever in a table, a log line or the audit log.

## Rules

- **Eligible types** (`config/urgent_messaging.php`), each with its audiences: `comms.urgent` (urgent school notice; guardians and staff), `attendance.absence` (absence alert; guardians only) and `safeguarding.alert` (staff only, notice-only text). Nothing else can be texted, even if a settings row is altered by hand. The other locked types (account mails, approvals, consent requests) are account or form notices, not urgent ones.
- **Opt-in.** One row per school in `school_messaging_settings`: SMS on or off, WhatsApp on or off, and the types for each, for guardians (`sms_types`, `whatsapp_types`) and for staff (`staff_sms_types`, `staff_whatsapp_types`). No row means off. A type can be chosen only for an audience it has (an absence alert never for staff, a safeguarding alert never for guardians). A type is texted only when its channel is on **and** the type is chosen. WhatsApp also needs the consent confirmation (recorded once, with its time) and, per type, an approved WhatsApp template in configuration; a type without one shows "Not available yet" and cannot be chosen.
- **Who may change it.** `UrgentMessagingRequest::authorize()` and `UrgentMessaging::change()` both require a Super Admin, or office staff (not Parent, Student or Teacher) with `setting-create`, at the current school. The service validates the input again (types must be eligible; WhatsApp types must have a template). The form carries the row's revision, so a stale form is refused. Each change writes the audit row `messaging.settings_changed` with the switches and type lists only.
- **Recipients.** Active members of the school, offered the type (module, portal and permission), who are
  - **guardians** (the parent portal) with a mobile number on their **parent record at this school** (`parents.mobile`, which the school office keeps). A guardian with no number at this school gets no text, even if another school has one; or
  - **staff** (teacher and admin portals) with no parent record at this school, on the mobile number on **their own account** (`users.mobile`). Any school employing the person can change that shared row, which is why it is texted only once the person has verified it (below): a number changed by anyone is no longer verified, and only the person can verify the new one.
  A person who is both is texted as a guardian first. Each person's number is the same for every notice at a school.
- **Number verification** (`TextNumbers`). A number is texted only after its owner has verified it at this school. The person asks for a code in the app (`POST /api/v2/me/text-messaging/verification`); a six-digit code goes by SMS to the number on record (never a number the caller names) through the same Twilio path as every text (`SendText`, delivery type `sms.messaging.verification`, billed to the school like any text). Codes expire after 10 minutes, are stored only as an HMAC (app key) of the row, the number's hash and the code, and are never logged, audited or kept in a delivery row (the job travels encrypted). One code a minute and three an hour per person (429 with `Retry-After`); five wrong codes clear the code and lock verification for an hour. `DeliveryGuard` texts a code only while it is still the code waiting for that number. Verification is bound to the person, the school and the number's hash: an update of `parents.mobile` or `users.mobile` through the models clears it (`Parents` and `User` model events), and a change written any other way no longer matches the hash. Audit: `messaging.verification_code_sent`, `messaging.number_verified`, `messaging.verification_locked` (no number, no code).
- **Existing numbers at rollout are unverified.** Every number recorded before this change, by the office or on an account, is not texted until its owner verifies it. This is deliberate: the office-recorded number may be stale or wrong, and texts are paid for by the school. Schools that had switched SMS or WhatsApp on should ask families and staff to open the app and verify their number.
- **Unverified numbers are skipped with a reason.** When a text would go to a number that is not verified, nothing is queued; one `notification_deliveries` row is written as `suppressed` with "Not sent: the mobile number has not been verified by its owner." (claimed once per notice, person and channel like a sent text). `DeliveryGuard` checks verification again just before Twilio is called.
- **The person's own choice** (`messaging_choices`). For each type the school texts them, a guardian or staff member chooses SMS, WhatsApp, both or neither (`PUT /api/v2/me/text-messaging`). Only channels the school switched on for their audience can be chosen; neither is always allowed. Without a choice a guardian gets what the school switched on (the school confirmed guardians' WhatsApp consent) and a staff member gets SMS, and WhatsApp only once they choose it. The choice is checked at queueing and again before sending. The school never sees or changes it: the settings page shows only how many people chose per type, and saving the school's setting leaves every choice as it is. Audit: `messaging.choice_changed`.
- **Numbers** are normalised to E.164: a 10-digit number starting with 0 is South African (`0821234567` becomes `+27821234567`); `27...`, `+...` and `00...` keep their country. Anything else is skipped.
- **What a text says.** For `attendance.absence`, and for a `comms.urgent` notice sent to selected people: only "You have an urgent message from <school>. Open the NavigateEd app." (in line with the email rule that an unverified address or a single-child notice gets no body). For a `comms.urgent` notice sent to the whole school: "Urgent from <school>: <title>. <message> Open the NavigateEd app for more." (title and message are at most 128 characters each; the text is cut at 300). WhatsApp sends the approved template instead, with `{{1}}` the school's name and, for a whole-school urgent notice with its own template, `{{2}}` the title and message.
- **One message per notice, person and channel.** `Notifier::send` claims `notification_dispatches` (`sms` or `whatsapp`) before queueing, so a retried fanout never texts again. Each message has its own `SendText` job and delivery key. A key that already has a Twilio message id is never sent again, so a job retry after Twilio accepted the message cannot repeat it.
- **Checked again at sending.** `DeliveryGuard` re-checks, just before Twilio is called: the person is still an active member, the school is active, the number on record is still the one queued (by hash) and is verified, the school still has the channel and type switched on for them, their own choice still allows it, plus every check the notice type already has (an absence still current, and so on). Otherwise the row is `suppressed` and nothing is sent.
- **Quiet hours** never hold these texts: only urgent and safety types are texted, and `SendText` sends at once at any hour. (Quiet hours for push are a separate change, Action Plan 1.5, which also exempts these types.)
- **Until Twilio is set up** (no SID, token or sender for a channel) nothing is queued for that channel; the settings page says so, and the school's choices are kept.
- **Retries.** Network errors and Twilio's 429 and 5xx answers are retried (3 tries, 60/300/900 s, as for mail and push). Any other refusal is recorded as `failed` and not retried.
- **Status callback.** `POST /webhook/messaging/twilio` acts only when `X-Twilio-Signature` matches the HMAC-SHA1 (auth token) of the exact callback URL plus the posted parameters, and `AccountSid` is ours. The body is read raw (the panel's input middleware trims strings, which would break the signature). `sent`, `delivered` (and WhatsApp's `read`), `failed` and `undelivered` update every row with that message id; `queued`, `sending` and `accepted` are ignored. A delivered, failed or undelivered message keeps that status when a late `sent` arrives. An unknown message id changes nothing (200, "Ignored").
- **Invoice line** (`MessagingCharges`, HQ billing SA.8). The platform invoice for a month carries the texts the school sent in the **month before** (South African months): a month's texts are known only once it has ended, while the invoice is for the month ahead. One line per channel (`kind` `messaging`), counting each message once (the attempt Twilio accepted), from the recorded `price_cents`, VAT on the line as for every line. A price in the invoice's currency (ZAR) is passed on as it is; another currency is converted at `platform_billing.messaging.exchange_rates` (`PLATFORM_BILLING_USD_ZAR_RATE`, rands per dollar as a decimal string, read to millionths without floating point), rounded half up once per currency on the line, and the rate is written on the line ("USD 0.24 at R 18.5000 per USD"). The draft is **blocked** (cannot be issued) while a message of that month may still be priced by the hourly lookup (sent within the lookup window and unpriced), or when a currency has no rate; recompute after. Messages Twilio never priced are counted on the line and not billed. The month's slot is unique per school and recomputing replaces the lines, so a month's texts are billed once; only the school's own deliveries are read. The statement says how much of each invoice is SMS and WhatsApp.
- **Pass-through pricing.** Each sent row records `segments` and Twilio's `price` as whole cents (`price_cents`, rounded half up from Twilio's decimal string without floating point) in Twilio's currency (`price_currency`, usually USD). Twilio prices a message after sending and its status callbacks do not normally include the price, so `messaging:fetch-prices` (hourly) asks Twilio for each message sent between 10 minutes and 7 days ago that has no price yet. The HQ report counts a message once (the attempt Twilio accepted) by its sent time in South African months.
- **Credentials.** `TWILIO_ACCOUNT_SID` and `TWILIO_AUTH_TOKEN` only from the environment (Secret Manager), read through `config/services.php`. Never in `settings`, never per school, never committed. Errors are reduced to "Twilio answered HTTP 400, error 21211." (Twilio's messages quote the number) and "Twilio could not be reached." (the connection error quotes the URL, which names the account).

## Data

| Table | Tenancy | Holds |
| --- | --- | --- |
| `school_messaging_settings` (new) | required school-owned (unique `school_id`, foreign key) | `sms_enabled`, `whatsapp_enabled`, `sms_types`, `whatsapp_types` (JSON lists), `whatsapp_consent_confirmed_at`, `changed_by`, `revision` |
| `notification_deliveries` (new columns) | nullable school-owned, as before | `segments`, `price_cents` (integer cents), `price_currency`; new index (`school_id`, `channel`, `sent_at`). Channels now `mail`, `push`, `sms`, `whatsapp`; status also `undelivered`. `recipient_hash` is the SHA-256 of the E.164 number |
| `notification_dispatches` | as before | claims with channel `sms` or `whatsapp` |
| `messaging_numbers` (new) | required school-owned (unique `school_id`, `user_id`) | `source` (`parent` or `account`), `number_hash` (SHA-256 of the E.164 number), `verified_at`, `code_hash` (HMAC), `code_expires_at`, `attempts`, `locked_until`, `codes_sent`, `codes_window_started_at`, `last_code_sent_at`. No number, no code |
| `messaging_choices` (new) | required school-owned (unique `school_id`, `user_id`, `type`) | `sms`, `whatsapp` |
| `school_messaging_settings` (new columns) | as before | `staff_sms_types`, `staff_whatsapp_types` |
| `platform_invoice_lines` | as before | new line kind `messaging` |

Migrations `2026_11_03_100000_create_school_messaging_settings` and `2026_11_07_100000_create_text_message_numbers_and_choices`. Each `down()` refuses while it holds rows (a setting, a price, a verified number, a choice, a staff opt-in).

## Endpoints

| Method and path | Name | Middleware | Notes |
| --- | --- | --- | --- |
| `GET /urgent-messaging` | `urgent-messaging.index` | `auth`, `school.context`, read-only years; `UrgentMessagingRequest` | The settings page |
| `PUT /urgent-messaging` | `urgent-messaging.update` | as above, `throttle:20,1` | `revision`, `sms_enabled`, `whatsapp_enabled`, `sms_types[]`, `whatsapp_types[]`, `whatsapp_consent` |
| `POST /webhook/messaging/twilio` | `webhook.messaging.twilio` | `web` (CSRF exempt for `webhook/*`), `throttle:messaging-webhook` (600 a minute per address) | Twilio's form-encoded status callback; JSON `{"error", "message"}`: 200 Recorded or Ignored, 400 invalid, 401 bad or missing signature or no token configured, 403 another account |
| `GET /saas/messaging?month=YYYY-MM` | `saas.messaging` | `platform.can:messaging.view` (Owner, Operations, Finance) | Per-school counts, segments, not delivered, awaiting a price and cost |
| `GET /api/v2/me/text-messaging` | | `auth:sanctum`, `school.context`, `role.any`; `TextMessagingRequest` (guardian, teacher or admin portal at this school; learners 403) | Your number (on record, source, last three digits, verified, code waiting, next code, locked) and, per texted notice, the channels offered and your choice |
| `PUT /api/v2/me/text-messaging` | | as above, `throttle:text-messaging` | `type`, `sms`, `whatsapp` |
| `POST /api/v2/me/text-messaging/verification` | | as above | Texts a code to your number on record |
| `POST /api/v2/me/text-messaging/verification/confirm` | | as above | `code` |

`throttle:text-messaging` is 20 a minute per person, method and path, a backstop to `TextNumbers`' own limits. The routes take no person id: each acts on the signed-in person only. `openapi.yaml` describes them (`TextMessagingResponse`); the generated Dart client needs regenerating.

## Screens

- Settings > Email and notifications > **SMS and WhatsApp** (navigation key `urgent-messaging`, permission `setting-create`): two switches, the WhatsApp consent confirmation, how many people verified a number, and a table of the eligible notices with what each message says, SMS and WhatsApp tick boxes for guardians and for staff (or "Not for guardians" / "Not for staff"), and how many people made their own choice.
- HQ > Billing: the invoice shows the SMS and WhatsApp lines with a note on how they are priced; the statement says how much of each invoice is SMS and WhatsApp.
- HQ > **SMS and WhatsApp**: a month picker and one row per school (SMS sent, SMS segments, SMS cost, WhatsApp sent, WhatsApp cost, not delivered, awaiting a price) with totals. No numbers, people or text.

## Notification types

No new types. `comms.urgent`, `attendance.absence` and `safeguarding.alert` have the SMS and WhatsApp transports; delivery types `sms.<type>` and `whatsapp.<type>`, plus `sms.messaging.verification` for codes. The person's SMS and WhatsApp choice is its own endpoint; `NotificationRules::CHANNELS` and the preferences API are unchanged.

## Configuration and secrets

| Key | Where | Notes |
| --- | --- | --- |
| `TWILIO_ACCOUNT_SID`, `TWILIO_AUTH_TOKEN` (secrets) | `config/services.php` (`twilio`) | Secret Manager `navigateed-twilio-account-sid`, `navigateed-twilio-auth-token`, mounted on the panel (webhook signature), the worker (sending) and the scheduler job (prices) by `infra/gcp/provision.sh` |
| `TWILIO_MESSAGING_SERVICE_SID` or `TWILIO_SMS_FROM` | env file | The messaging service is preferred; otherwise an SMS sender (number or approved alphanumeric sender ID) |
| `TWILIO_WHATSAPP_FROM` | env file | `whatsapp:+27...`; without it WhatsApp also goes through the messaging service |
| `TWILIO_WHATSAPP_TEMPLATE_NOTICE` | env file (`config/urgent_messaging.php`) | Content SID (`HX...`) of the approved notice-only template; needed for any WhatsApp |
| `TWILIO_WHATSAPP_TEMPLATE_URGENT_NOTICE` | env file | Optional: the approved template carrying a whole-school urgent notice's text |
| `TWILIO_STATUS_CALLBACK_URL` | env file | Optional: the exact URL Twilio signs; default `APP_URL/webhook/messaging/twilio` |
| `TWILIO_DEFAULT_COUNTRY_CODE` | env file | Default `27` |
| `PLATFORM_BILLING_USD_ZAR_RATE` | env file (`config/platform_billing.php`, `messaging.exchange_rates.USD`) | Rands per US dollar for the invoice line, for example `18.50`. Set before creating invoices for a month with texts; update it monthly as the owner decides |
| `urgent_messaging.verification` | `config/urgent_messaging.php` | Code lifetime (10 minutes), wrong tries (5), lockout (60 minutes), resend wait (60 s), codes per hour (3) |

## What the owner must set up in Twilio

1. **Account.** Create (or upgrade) the one NavigateEd Twilio account. Put the Account SID and the primary Auth Token into Secret Manager (`navigateed-twilio-account-sid`, `navigateed-twilio-auth-token`) for staging and production, then re-run `infra/gcp/provision.sh` so the panel, worker and scheduler mount them. Rotating the Auth Token: promote the secondary token in Twilio and update the secret together, or callbacks are refused (401) until both match.
2. **SMS sender.** Create a Messaging Service and add a sender: for South Africa, register an alphanumeric sender ID (for example "NavigateEd"; one-way, guardians cannot reply) or buy a number that can send SMS to South Africa. Turn on Geo Permissions for South Africa (and any other country guardians' numbers are in). Put the Messaging Service SID (`MG...`) in `TWILIO_MESSAGING_SERVICE_SID` (or the sender in `TWILIO_SMS_FROM`).
3. **WhatsApp sender.** Register a WhatsApp Business sender in Twilio (Meta Business Manager verification, a display name such as "NavigateEd", a number not already on WhatsApp). Put it in `TWILIO_WHATSAPP_FROM` as `whatsapp:+27...`, or add it to the messaging service.
4. **WhatsApp templates.** Business-initiated WhatsApp messages need templates Meta has approved. In Twilio's Content Template Builder create, in the *Utility* category, and submit for WhatsApp approval:
   - a notice-only template, for example "You have an urgent message from {{1}}. Open the NavigateEd app." (`{{1}}` = school name) into `TWILIO_WHATSAPP_TEMPLATE_NOTICE`;
   - optionally an urgent-notice template, for example "Urgent from {{1}}: {{2}} Open the NavigateEd app for more." (`{{2}}` = the notice's title and message, at most 300 characters) into `TWILIO_WHATSAPP_TEMPLATE_URGENT_NOTICE`.
   Add Afrikaans and isiZulu versions later if wanted; this step sends one language.
5. **Status callback.** Nothing to configure in Twilio: every message names `https://<panel>/webhook/messaging/twilio` as its `StatusCallback` (or `TWILIO_STATUS_CALLBACK_URL`, which must be exactly the public URL, scheme and host included, or signatures fail). Both staging and production env files leave `APP_URL` empty today (the Cloud Run address is used), so set `APP_URL` or `TWILIO_STATUS_CALLBACK_URL` to the panel's public address before the first message.
6. **Spend.** Set a usage trigger or balance alert in Twilio. Staging spend counts toward the owner's US$10 a month ceiling for staging and email.

## Acceptance tests (`Platform/UrgentMessagingTest`)

| Given | When | Then | Test |
| --- | --- | --- | --- |
| School A switched SMS and WhatsApp on for urgent notices | a whole-school urgent notice goes through `Notifier` | one SMS (messaging service, the title and message, the status callback URL) and one WhatsApp template message (the urgent template, school and text) go to the guardian's office-recorded number with basic auth; `sms.comms.urgent` and `whatsapp.comms.urgent` rows are `sent` with Twilio's message id and 1 segment, a hash of the number and no number | `test_an_urgent_notice_is_texted_by_sms_and_whatsapp_through_twilio_for_an_opted_in_school` |
| SMS for both types, WhatsApp for absences | an absence alert and an urgent notice to one family, both naming the child | only the notice-only text and notice-only template; the child's name in no request | `test_an_absence_alert_or_a_notice_to_one_family_never_carries_its_text` |
| No setting; then SMS on with no types and WhatsApp on without consent | an urgent notice | nothing sent, no rows | `test_a_school_that_has_not_opted_in_gets_nothing_sent` |
| Opted in | choose `comms.notice` for SMS; or alter the row to include `comms.notice` and `fees.reminder`, then send them | validation error, row unchanged; nothing sent | `test_a_non_urgent_type_is_never_sent_by_sms` |
| A teacher, a parent, a learner; the service called as a teacher | open or save the page | 403; no row, no audit row | `test_only_office_staff_with_the_settings_permission_change_the_setting` |
| The Super Admin | open the page; save WhatsApp without consent; save with it; a stale form; WhatsApp for a type without a template | the eligible notices listed; refused; saved with an audit row; refused; refused | `test_the_school_office_switches_sms_and_whatsapp_on_from_settings` |
| School B opted in, School A not; then A opted in; a School A guardian with a number only at School B | School A notices | nothing sent to School A's guardian; School B's guardian never texted by School A; School B's number never used; School A's change leaves School B's row unchanged | `test_school_a_never_texts_school_b_guardians_and_school_b_settings_do_not_affect_school_a` |
| A queued text | the same fanout twice; the job run twice | one job, one Twilio request, one row | `test_a_retry_or_a_repeated_fanout_never_repeats_a_sent_message` |
| Two queued texts | the number changes before one, the school opts out before the other | both `suppressed`, nothing sent | `test_a_changed_number_or_a_withdrawn_opt_in_stops_a_queued_text` |
| 02:30 in Johannesburg | an absence alert | sent at once | `test_quiet_hours_are_not_applied_to_urgent_texts` |
| Twilio refuses quoting the number, then cannot be reached | an urgent notice by SMS and WhatsApp | both `failed` with "Twilio answered HTTP 400, error 21211." and "Twilio could not be reached."; token, SID, number and Twilio's text in no log line, delivery row, audit row or failed job | `test_no_credentials_number_or_twilio_message_text_reaches_logs_rows_or_audit` |
| A sent text | a signed `delivered` callback with segments and price; a late `sent`; `undelivered` with a code | delivered, 2 segments, 8 cents USD; still delivered; undelivered with the code only | `test_a_signed_status_callback_updates_the_delivery_with_its_segments_and_price` |
| A sent text | a wrong, empty or other-token signature; another account; a signature for another URL; no token configured | 401 / 401 / 401 / 403 / 401 / 401; row unchanged | `test_a_callback_with_a_bad_signature_another_account_or_no_token_is_refused` |
| A sent text | a callback for an unknown message id; an intermediate status | 200 Ignored; nothing changes | `test_a_callback_for_an_unknown_message_is_ignored` |
| The route | inspected | `webhook/messaging/twilio`, `throttle:messaging-webhook` | `test_the_route_is_rate_limited_and_outside_csrf` |
| Texts at Schools A and B this month and one last month | `messaging:fetch-prices`; Finance opens HQ; Content and a school Super Admin open it | the unpriced message gets Twilio's price (16 cents) by one GET; per-school costs for the month only, no numbers; 403; 403 | `test_prices_twilio_reports_later_are_recorded_and_hq_sees_each_schools_count_and_cost` |
| Recorded numbers and Twilio prices | normalised | South African by default, other countries kept, junk skipped; prices rounded half up to whole cents | `test_recorded_numbers_are_read_as_south_african_unless_they_say_otherwise` |

### Follow-ups (`Platform/TextMessagingRecipientsTest`, `Platform/MessagingInvoiceLineTest`)

| Given | When | Then | Test |
| --- | --- | --- | --- |
| School A texts urgent notices to guardians | Farid reads his state, asks for a code, types it; a notice is sent before and after | unverified first (nothing sent), one SMS with a code to his number, a `sms.messaging.verification` row; then verified and the notice texted; asking again sends nothing; the code and number in no row, audit or log line | `test_a_guardian_verifies_their_number_with_a_code_sent_by_sms_and_only_then_is_texted` |
| A code sent | a wrong code; a malformed one; the right one after 11 minutes; five wrong codes on a new one; the right one and a new code while locked; an hour later | 422 with tries left; 422; expired; locked until the shown time, audited; still refused; a new code verifies | `test_a_wrong_or_expired_code_counts_and_too_many_wrong_codes_lock_verification` |
| | codes asked for at once, a minute apart, a fourth in the hour, an hour later | 429 with `Retry-After`; sent; 429; sent; the last code verifies | `test_codes_are_rate_limited_per_person` |
| A verified number | the office changes it; it is changed back; changed by a raw write | not verified, the notice skipped once with the reason and the new number's hash; still unverified; skipped | `test_a_changed_number_is_no_longer_verified_and_unverified_numbers_are_skipped_with_a_reason` |
| Numbers recorded before rollout | notices sent | none texted, each skipped with the reason | `test_numbers_recorded_before_verification_started_are_not_texted` |
| SMS for both types, WhatsApp for urgent | Farid chooses WhatsApp only, then neither; a channel or type not offered; a bad value; the office opens and saves its settings | per type: only WhatsApp, then nothing, absences still by SMS; 422 and nothing changed; the page shows "1 person"; his choice still wins | `test_a_guardian_chooses_sms_whatsapp_both_or_neither_for_each_notice` |
| A teacher | guardians only opted in; staff opted in; Tara verifies her account number and chooses WhatsApp; absence alerts for staff or safeguarding for guardians; Tara's account number changes | nothing; skipped as unverified; SMS only, then both; validation errors; unverified again | `test_staff_are_texted_only_when_the_school_opts_them_in_and_they_verify` |
| A learner; a teacher; no token | every endpoint; opting staff in; reading | 403 and nothing written or sent; 403; 401 | `test_learners_and_staff_without_the_settings_permission_are_refused` |
| Farid and Mona at School A | Mona types Farid's code, asks for a code naming Farid, changes her choice naming Farid | refused; the code goes to Mona's number; only Mona's choice changes; Farid's code still verifies Farid | `test_another_guardian_cannot_verify_someone_elses_number_or_change_their_choice` |
| Farid verified with a choice at School A, a parent record at School B | School B's view; School B's verified guardian sent a School A notice; School B's guardian calls the API at School A | School B sees no verification, choice or count and Farid is not verified there; nothing sent; 403 | `test_school_b_never_sees_or_uses_school_as_numbers_choices_or_verifications` |
| Texts of Schools A and B in September to November, one unpriced within the lookup window, one never priced, one never accepted, one priced in rand | Finance runs November; prices it and sets the rate; recomputes, runs again, issues; a late text; runs December | blocked (price waiting / no rate); A: SMS R 4.44 (VAT 67) and WhatsApp R 0.19 (VAT 3) for October only; B: R 7.44 of its own; billed once; the statement and invoice show it; December carries November's | `test_the_invoice_carries_last_months_texts_once_in_cents_and_never_another_schools` |
| | rates read | to millionths, no floats; a bad rate refused | `test_rates_are_read_without_floating_point_and_a_bad_rate_is_refused` |

## Logs and metrics

| Log line | Level | Context |
| --- | --- | --- |
| `notification_delivery` | info | as for mail and push, channel `sms` or `whatsapp` |
| `twilio_status`, `twilio_status_rejected` | info, warning | Twilio's status, result, rows updated / HTTP status and reason |
| `messaging_prices_fetched` | info | messages checked and priced |
| `notification_delivery_abandoned` | error | the last attempt failed (`SendText`) |

## Not in this step

- **App screens** for verifying a number and choosing SMS or WhatsApp: the API is ready; the Flutter apps and the regenerated Dart client (`packages/eschool_api`) are a separate change. Office staff who use only the web panel have no screen yet either.
- **A guardian entering their own number.** The number verified is the one on record (the office's parent record, or the staff member's account); a wrong one is corrected by the office.
- **Per-guardian WhatsApp consent beyond the choice.** The school's consent confirmation still covers guardians by default; a guardian can switch WhatsApp off.
- **Exchange rates by date.** One configured rate is used when a draft is computed and written on the line; a rate per month or fetched from a provider is a later step.
- **Replies and STOP handling** (incoming messages): the alphanumeric sender is one-way; Twilio's own opt-out handling on numbers applies.
- **Translated texts and templates.** The text uses the queue's language (English by default); Afrikaans and isiZulu WhatsApp templates need their own approval.
- **Staging and production verification** with a real Twilio account: nothing in this step has called Twilio (every test fakes it).
