# 0003. Thin app shells over shared packages, web builds on Firebase Hosting, portal hostnames

Status: Accepted (Phase 0). The hostnames are the default taken for Q0.2 in [phase-0-questions.md](../phases/phase-0-questions.md) until the product owner answers. Source: Build Bible section 3.4, Phase 0 steps 9 and 10.

## Context

- `e-school/` (learner and parent) and `e-school-teacher/` (teacher) are separate Flutter apps that duplicate 144 files (33 byte-identical, 30 shared widgets), hard-code `baseUrl` in `lib/utils/constants.dart`, have no `web/` folder, no flavours and no deep links, and only two tests each.
- `packages/eschool_core` already holds the Material 3 theme, the `ApiClient`, seven models and five widgets, with theme, contrast and golden tests. The look has to stay exactly as it is.
- Every profile has to work on a phone, a tablet, a desktop and in a browser; the teacher app has to be able to take more flavours later; staging and production builds should differ in configuration only; and new endpoints should reach the apps through typed clients (see [ADR 0002](0002-api-v2-and-openapi.md)).
- One Firebase project (`navigateed-d8c06`) already serves FCM, Crashlytics and the documentation site's Hosting.

## Options

1. **Keep two apps and copy code between them.** No restructuring, but every new module is written twice and drifts.
2. **Merge into one app** with a role switch. One codebase, but one store listing for very different audiences, a larger binary for everyone, and a big-bang migration of two installed apps.
3. **Two thin shells over shared packages**, with Flutter web builds of both shells hosted on Firebase Hosting.
4. **A separate web frontend** (for example React) for the portals. Best-in-class web tooling, but a second UI codebase, a second implementation of the theme, and two teams' worth of work for one team.

## Decision

Option 3.

```
packages/
  eschool_core/            the existing theme, ApiClient and widgets, plus the layout
                           helpers (Breakpoints, AdaptiveScaffold, ContentColumn)
  eschool_api/             Dart models and clients generated from openapi.yaml
  eschool_session/         sign-in, /me/context, the current school and child,
                           ModuleGate, deep links, forced updates
  eschool_notifications/   FCM, local notifications, the Notifications settings screen
e-school/                  learner and parent shell; web build
e-school-teacher/          teacher shell (FLAVOR=teacher); web build
```

- **Configuration** comes from `--dart-define` (`API_BASE_URL`, `ENV`, `FLAVOR`) through `AppConfig`, replacing the constants. The defines are the only difference between a staging and a production build, and no key or secret is compiled in.
- **Web**: both shells build with `flutter build web`; plugins that only work on phones sit behind conditional imports, with a web fallback; Hive on IndexedDB; Sanctum cookie mode (`EnsureFrontendRequestsAreStateful` with the portal origins). The builds are deployed to Firebase Hosting sites by the deploy workflows (`.github/workflows/deploy-staging.yml`, `deploy-production.yml`).
- **Hostnames** (Q0.2 default):

| Host | Serves | Hosted on |
| --- | --- | --- |
| `app.navigateed.co.za` | Learner and parent web portal (`e-school` web build) | Firebase Hosting |
| `teach.navigateed.co.za` | Teacher portal (`e-school-teacher` web build) | Firebase Hosting |
| `admin.navigateed.co.za` | Admin panel, public pages and the API | Cloud Run `navigateed-admin` |
| `staging-app.`, `staging-teach.`, `staging-admin.navigateed.co.za` | The same for staging | Staging Firebase Hosting sites and Cloud Run |

  Until DNS is in place, each environment also answers on its default `*.web.app` and `*.run.app` addresses, and the apps' `API_BASE_URL` points there.
- **Firebase**: one project with an app registration per environment for FCM and Crashlytics; Hosting sites per environment (`engineering/runbooks/environments.md` lists them).

## Consequences

- New screens are written once, in a package both shells can use. Existing screens move into packages in Phase 0 with no visual change on phones; the theme, contrast and golden tests guard the look.
- The web portals share the apps' code, so every screen must work at phone, tablet and desktop widths (Breakpoints at 600 and 1024 px) and without mobile-only plugins.
- Cookie mode needs the portals and the API under one registrable domain: the session and `XSRF-TOKEN` cookies are set for `.navigateed.co.za` so the portal can read the CSRF cookie and send it back. CORS must allow only the portal origins.
- **Open item for the product owner**: with staging hostnames under the same `navigateed.co.za` parent, production's parent-domain cookies are also sent to the `staging-*` hosts, and the fixed `XSRF-TOKEN` cookie name can collide between the two environments in one browser. Staging must at least use its own `SESSION_COOKIE` name. The safer choice is a separate registrable domain for staging (for example `navigateed-staging.co.za`); the deploy configuration takes the hostnames as variables, so either works without code changes.
- Store builds and web builds now come from the same commits; the release runbook builds both and records the pairing.
