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

Code that is, or can be made, identical in both apps and does not depend on
anything specific to one of them: data models, helpers, and widgets that take
everything they need as parameters. Things like colours, label keys and the
`UiUtils` helper class differ between the apps today, so code that needs them
stays in the app until they are shared as well.

When a file in one app is a copy of a file in the other, move it here, replace
the two copies' imports with `package:eschool_core/eschool_core.dart`, and
export the file from `lib/eschool_core.dart`. Files here use `snake_case` names.

## Checks

```sh
flutter pub get
flutter analyze --no-fatal-infos
flutter test
```

CI runs the same commands for this package and for each app.
