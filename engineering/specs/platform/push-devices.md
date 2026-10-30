# Push notifications on every signed-in device

Action Plan step 0.3. Owner decision (29 October 2026): parents, teachers and
learners use NavigateEd on more than one device (a phone and a tablet, a
parent's phone handed to a child in junior child mode), and every signed-in
device must receive notifications. This supersedes the earlier token rule
"signed in on one device per app" in [security-fixes.md](security-fixes.md).

## Behaviour

- **Several devices per app.** Signing in to an app on a new device keeps the
  account's other devices of that app signed in, up to
  `AppTokens::MAX_DEVICES_PER_APP` (5). Signing in beyond that signs the oldest
  device of that app out.
- **One push registration per device.** `user_devices` holds one row per FCM
  token: the human it belongs to, platform (`android`, `ios`), app, the app
  sign-in token (`personal_access_tokens.id`) that registered it, last seen and
  revoked times. The token is encrypted at rest; its SHA-256 is unique.
- **Every device receives each notification.** `Notifier` queues one `SendPush`
  per active device, each with its own `notification_deliveries` row, so a retry
  never repeats a message another device already received.
- **A device is active** while it is not revoked and, when a sign-in registered
  it, while that sign-in token still exists. Signing out, a password change,
  deactivation or any other token revocation therefore ends that device's
  notifications with no separate bookkeeping.
- **Signing out ends only that device.** The account's other devices stay signed
  in and keep receiving notifications.
- **A shared phone belongs to whoever signed in last.** Registering a token that
  another account held moves it, and clears the other account's projection, so
  one phone never receives two people's notifications.
- **Unregistered tokens are forgotten.** When FCM answers `UNREGISTERED`, that
  device is revoked for the recipient only.
- **At most 10 registrations per account** (`PushDevices::MAX_ACTIVE`); the least
  recently seen beyond that are revoked.
- **Compatibility.** `users.fcm_id` / `device_type` remain a projection of the
  latest registration for installed clients and older code. An account with a
  legacy value and no matching registration still receives pushes there, unless
  that token has been registered at all (a registration, active or not,
  decides). The migration copies each existing legacy value into
  `user_devices` (unbound to a sign-in token) so nobody stops receiving
  notifications at deploy. Such an unbound device stays active only while the
  account still holds a sign-in from before it was copied, and signing out of
  a sign-in that registered no device ends the account's unbound devices, even
  when `users.fcm_id` has since moved to a device signed in later.
- **The worker rechecks the device.** `DeliveryGuard` refuses a queued push whose
  token is no longer one of the recipient's active devices.

Registration happens where the apps already send `fcm_id` and `device_type`: the
app sign-in (`PortalSession::signIn` for the learner, parent, teacher,
facilitator and vendor apps) and guardian invitation acceptance. A dedicated
token-refresh endpoint, and app changes to call it, are a follow-up.

## Privacy and security

- Tokens are encrypted (`encrypted` cast), hidden from serialisation, and never
  logged; delivery rows keep the existing recipient hash only.
- `user_devices` is a global table (one human across schools), registered in
  `SchoolOwnedTables::GLOBAL`; notifications still carry their own school.
- The cap on devices per app keeps the number of live tokens bounded, which was
  the purpose of the earlier single-device rule.

## Acceptance tests

| Given | When | Then | Test |
| --- | --- | --- | --- |
| An account signed in on an Android phone and an iPhone | a notification is sent | one push per device; the iOS one carries the visible notification block | `PushDevicesTest::test_every_signed_in_device_receives_the_notification` |
| Two signed-in devices | one signs out | only the other keeps receiving pushes | `PushDevicesTest::test_ending_one_sign_in_stops_only_that_devices_notifications` |
| Two signed-in devices | one sign-in token is deleted (password change, revocation) | that device stops receiving pushes | `PushDevicesTest::test_a_deleted_sign_in_token_ends_its_device_without_bookkeeping` |
| A phone registered to one account | another account signs in on it | the phone belongs to the new account only | `PushDevicesTest::test_a_shared_phone_moves_to_the_account_that_signed_in_last` |
| A queued push | its device signed out before the worker ran | suppressed, nothing sent | `PushDevicesTest::test_a_queued_message_for_a_device_that_signed_out_is_suppressed` |
| FCM answers `UNREGISTERED` | for one of two devices | that device is revoked; the other stays | `PushDevicesTest::test_an_unregistered_token_is_forgotten_for_that_account` |
| Any registration | stored | token encrypted and not serialised | `PushDevicesTest::test_tokens_are_stored_encrypted_and_never_serialised` |
| More than 10 registrations | registered | the 10 most recent stay active | `PushDevicesTest::test_only_the_most_recent_devices_are_kept` |
| An installed client with only `users.fcm_id` | a notification is sent | it still receives it | `PushDevicesTest::test_an_account_without_a_device_registration_still_uses_its_legacy_token` |
| A client signed in before deploy, then a new phone signs in | the old client signs out | the old phone stops receiving pushes; the new phone keeps them | `PushDevicesTest::test_signing_out_an_old_client_ends_its_device_after_another_device_signed_in` |
| A client signed in before deploy, then a new phone signs in | the password is changed from the new phone | the old phone stops receiving pushes | `PushDevicesTest::test_an_old_clients_device_ends_when_its_sign_in_is_revoked` |
| An app signed in on a second device | the first device calls the API | it is still signed in | `AppTokensTest::test_signing_in_on_another_device_keeps_the_first_device_signed_in` |
| Five devices signed in to one app | a sixth signs in | the oldest is signed out | `AppTokensTest::test_signing_in_beyond_the_device_limit_signs_the_oldest_device_out` |
| An account on two devices of one app | one signs out | the other stays signed in | `AppTokensTest::test_signing_out_ends_only_that_device` |

## Not in this change

- A `PUT /api/v2/me/devices` endpoint for FCM token refresh, and the app change in
  `packages/eschool_notifications` to call it (needs the OpenAPI and generated
  SDK update).
- Real device and provider verification on staging (pending staging).
