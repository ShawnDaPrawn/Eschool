# eschool_core

Code shared by the two Flutter apps, `e-school` (student and parent) and
`e-school-teacher`. Both depend on it by path:

```yaml
dependencies:
  eschool_core:
    path: ../packages/eschool_core
```

Import everything through the one library:

```dart
import 'package:eschool_core/eschool_core.dart';
```

## What goes here

Code that is, or can be made, identical in both apps: data models, helpers,
the Material 3 theme (`lib/src/theme/`), the build settings (`AppConfig`), the
API client, and the widgets and cubits both apps had copies of.

| Folder in `lib/src/` | Holds |
|---|---|
| `api/` | `ApiClient`, `ApiException`, the app-side error codes |
| `config/` | `AppConfig`: `API_BASE_URL`, `ENV`, `FLAVOR` from `--dart-define` |
| `cubits/` | Cubits both apps use; each takes a repository interface |
| `l10n/` | `AppLocalization`, `AppLocalizationDelegate`, the shared label keys |
| `models/` | Data models |
| `repositories/` | Interfaces the shared cubits read through; each app's repositories implement them, and tests pass fakes |
| `shell/` | `AppShell`: what shared widgets need from the app they run in |
| `theme/` | The Material 3 theme. The only place allowed to name colours |
| `ui/` | `SharedUiUtils` (sizes and helpers), `SharedAssets` (image paths), `SharedRoutes` (route names) |
| `utils/` | Extensions, file-name sanitiser, list animations |
| `widgets/` | Shared widgets |

A shared widget reads labels with `SharedUiUtils.getTranslatedLabel`, which
uses the running app's `assets/languages/` files, and the label keys it needs
must be in `lib/src/l10n/label_keys.dart` and in `sharedLabelKeys`; each app's
`labelKeys.dart` re-exports that file, and each app's
`test/shared_label_keys_test.dart` checks its language files have them. Images
a shared widget draws stay in each app's `assets/` under the same path and are
named in `SharedAssets`.

Where the two apps still behave differently, the widget asks the app through
`AppShell.of(context)` instead of importing app code: the error code to label
mapping, how an attachment opens, the button next to an attachment and the
bottom bar's height. Each app installs one `AppShell` in `MaterialApp.builder`.
A widget test that pumps a shared widget needs one too; `test/support/` has a
harness that installs it and serves test labels.

The theme's rules (which colour role to use where, which components to use)
are in the wiki's UI Standards page. `lib/src/theme/` is the only place
allowed to name colours; `test/theme_colors_test.dart` enforces that.

When a file in one app is a copy of a file in the other, move it here, replace
the two copies' imports with `package:eschool_core/eschool_core.dart`, and
export the file from `lib/eschool_core.dart`. If the copies differ only in a
value, give the shared widget a parameter or an `AppShell` entry rather than
changing how either app looks. Files here use `snake_case` names.

## Checks

```sh
flutter pub get
flutter analyze --no-fatal-infos
flutter test
```

CI runs the same commands for this package and for each app.
