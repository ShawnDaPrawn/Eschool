# Contributing

This repository is five projects in one checkout. Change the project you mean to change.

| Directory | What it is | Check before a pull request |
| --- | --- | --- |
| `admin-panel/` | Laravel admin panel, public website, and API | `php artisan test` |
| `e-school/` | Flutter app for students and parents | `flutter analyze --no-fatal-infos` and `flutter test` |
| `e-school-teacher/` | Flutter app for teachers | `flutter analyze --no-fatal-infos` and `flutter test` |
| `documentation/` | End-user documentation site | `npm ci` and `npm run build` |
| `wiki/` | Developer wiki | Edit the Markdown here. A push to `main` copies `wiki/` over the Wiki tab. |

Leave the Dart package names `eschool` and `eschool_teacher` as they are. Imports across each app use them.

The full guide (branches, commit style, how to add an admin module or an API endpoint, and how to edit the wiki) is [wiki/Contributing.md](wiki/Contributing.md). The map of the tree is [wiki/Repository-Layout.md](wiki/Repository-Layout.md).
