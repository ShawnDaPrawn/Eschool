# eschool_people

The people screens both NavigateEd apps share, built once: a guardian's
**Approvals** and **Invitation**, **My school**, and the **profile picture**
(an avatar of the set in `eschool_core`, or a photo). Depend on it by path and
import the one library:

```yaml
dependencies:
  eschool_people:
    path: ../packages/eschool_people
```

```dart
import 'package:eschool_people/eschool_people.dart';
```

## What is here

| Screen | Files in `lib/src/` | Endpoint (Phase 1 contract) |
|---|---|---|
| Approvals: children waiting for and given the guardian's approval; approving shows the consent text, withdrawing asks first | `approvals/` | `GET /api/v2/guardian/approvals`, `POST /api/v2/guardian/approvals/{learner}` |
| Invitation: opened from the link in the email; the school and the children, a new password when needed, then signed in | `invitation/` | `GET /api/v2/guardian/invitations/{token}`, `POST .../{token}/accept` |
| My school: crest, name, contacts (a tap calls or writes), documents, term dates; a parent with children at two schools picks the school | `my_school/` | `GET /api/v2/schools/{school}/my-school` |
| Profile picture: the sixteen avatars, the person's own kind first, or a photo that waits for the school | `avatar/` | `GET` and `PUT /api/v2/me/avatar` |

Each screen has a cubit and a repository interface with an implementation
over the app's `ApiClient` (so calls carry the token, cookies, `X-School-Id`
and 401 handling), and a `withRepository` factory. The tests use fakes. The
request and response models are written by hand next to each repository,
reusing the generated `SchoolSummary`, until the endpoints are in
`admin-panel/openapi.yaml`; then they are replaced by the generated
`eschool_api` models and client.

An endpoint that answers 404 is not deployed yet: each repository file has a
`...Deployed()` check for `AvailableOnly` (`eschool_session`), which keeps the
screen's entry point hidden until then.

Every label is a shared key in `eschool_core`'s `label_keys.dart`, so both
apps' language files carry it.

## Checks

```sh
flutter pub get
flutter analyze --no-fatal-infos
flutter test
```
