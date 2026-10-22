# Contributing

This repository is several projects in one checkout. Change the project you mean to change.

| Directory | What it is | Check before a pull request |
| --- | --- | --- |
| `admin-panel/` | Laravel admin panel, public website, and API | `php artisan test`, `vendor/bin/phpstan analyse --memory-limit=2G --no-progress`, `vendor/bin/pint --test <files you changed>`; for a panel page also the Playwright checks in `admin-panel/e2e/` |
| `e-school/` | Flutter app for students and parents | `flutter analyze --no-fatal-infos` and `flutter test` |
| `e-school-teacher/` | Flutter app for teachers | `flutter analyze --no-fatal-infos` and `flutter test` |
| `packages/eschool_core/` | Dart package with the code both Flutter apps share | `flutter analyze --no-fatal-infos` and `flutter test`, then the same in both apps |
| `packages/eschool_session/` | Sign-in, `/me/context`, force update and deep links for both apps | `flutter analyze --no-fatal-infos` and `flutter test`, then the same in both apps |
| `packages/eschool_notifications/` | Push and local notifications for both apps | `flutter analyze --no-fatal-infos` and `flutter test`, then the same in both apps |
| `packages/eschool_api/` | Dart client generated from `admin-panel/openapi.yaml` (never edited by hand) | `tool/generate.sh` after changing `openapi.yaml`, then `flutter analyze --no-fatal-infos` and `flutter test`; CI fails if the generated code differs |
| `documentation/` | End-user documentation site | `npm ci` and `npm run build` |
| `wiki/` | Developer wiki | Edit the Markdown here. A push to `main` copies `wiki/` over the Wiki tab. |
| `engineering/` | Phase plans, specs, decision records, runbooks, security and self-test records | A spec per feature in `engineering/specs/`; the CHANGELOG.md line for your change |
| `infra/` | Cloud provisioning and deploy scripts | `bash -n`, `shellcheck` and, for workflows, `actionlint` |

Both apps also build for the web: after changing an app, `flutter build web --release --dart-define-from-file=config/staging.json` must still succeed (CI job `apps-web`).

Leave the Dart package names `eschool` and `eschool_teacher` as they are. Imports across each app use them.

The full guide (branches, commit style, how to add an admin module or an API endpoint, and how to edit the wiki) is [wiki/Contributing.md](wiki/Contributing.md). The map of the tree is [wiki/Repository-Layout.md](wiki/Repository-Layout.md).
