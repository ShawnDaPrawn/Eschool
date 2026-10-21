The rules every screen in the two Flutter apps follows. Both apps use one Material 3 theme, defined once in the shared package [`packages/eschool_core`](https://github.com/ShawnDaPrawn/Eschool/tree/main/packages/eschool_core/lib/src/theme). Colours, shapes and type come from that theme, so the brand can change in one file and every screen follows. Tests in each app fail when a colour is hard-coded, and a screenshot test in the shared package fails when the theme changes how the shared widgets look.

## Where the theme lives

| File | What it holds |
|---|---|
| [`brand_colors.dart`](https://github.com/ShawnDaPrawn/Eschool/blob/main/packages/eschool_core/lib/src/theme/brand_colors.dart) | `BrandColors`: the brand colours (primary, accent and the four status colours). The only place to change the brand. |
| [`app_theme.dart`](https://github.com/ShawnDaPrawn/Eschool/blob/main/packages/eschool_core/lib/src/theme/app_theme.dart) | `AppTheme.light()`: the `ThemeData` both apps pass to `MaterialApp`. Builds the full colour scheme from `BrandColors.primary` with `ColorScheme.fromSeed(dynamicSchemeVariant: DynamicSchemeVariant.fidelity)`, which keeps the brand colour exact, and the tertiary roles from `BrandColors.accent` the same way. Error colours are Material 3's own. Sets the corner radii (`AppTheme.radius` 10, `AppTheme.bottomSheetRadius` 20). |
| [`status_colors.dart`](https://github.com/ShawnDaPrawn/Eschool/blob/main/packages/eschool_core/lib/src/theme/status_colors.dart) | `StatusColors`: success, warning, danger and info, which Material 3's colour scheme has no roles for, each with its own text colour (`onSuccess`, `onWarning`, `onDanger`, `onInfo`). A `ThemeExtension`, read with `StatusColors.of(context)`. |
| [`readable_on.dart`](https://github.com/ShawnDaPrawn/Eschool/blob/main/packages/eschool_core/lib/src/theme/readable_on.dart) | `colorScheme.readableOn(background)`: the scheme's light or dark text colour, whichever reads better on a colour that isn't a scheme role. |

Each app's `lib/app/app.dart` uses `theme: AppTheme.light(font: GoogleFonts.poppinsTextTheme)`.

## Colours

Read every colour from the theme:

```dart
final colors = Theme.of(context).colorScheme; // or UiUtils.getColorScheme(context)
final status = StatusColors.of(context);
```

Pick the role by what the colour is for, not by how it looks:

| You are colouring | Use |
|---|---|
| Main text and icons | `onSurface` |
| Secondary text, hints, captions | `onSurfaceVariant` |
| Page, sheet and dialog backgrounds | `surface` |
| Grey fills: fields, chips, cards on a page | `surfaceContainerLow` (lighter: `surfaceContainerLowest`; darker: `surfaceContainer`, `surfaceContainerHigh`, `surfaceContainerHighest`) |
| Borders and dividers | `outline` (strong), `outlineVariant` (subtle) |
| Brand fills: headers, primary buttons, selected states | `primary` |
| Text and icons on `primary` | `onPrimary` |
| Brand accent fills: chips, highlights, tints | `tertiaryContainer` (the exact accent colour), with `onTertiaryContainer` on top |
| Accent text, icons and borders | `tertiary` (a darker shade of the accent that reads on the page) |
| Errors and destructive actions | `error`, with `onError` on top |
| Present, paid, approved | `StatusColors.of(context).success` |
| Pending, due soon | `.warning` |
| Absent, rejected, overdue | `.danger` |
| Neutral information | `.info` |
| Text on a status colour | that status's own: `.onSuccess`, `.onWarning`, `.onDanger`, `.onInfo` |
| Text on a colour from the server, or a background chosen at runtime | `colorScheme.readableOn(background)` |
| A dark pill or banner | `inverseSurface`, with `onInverseSurface` on top |
| Content on video, photos or a scrim | `onInverseSurface` |
| Shadows | `shadow` |
| Dimming behind a dialog | `scrim` |

`secondary` and its relatives are the brand's second colour. Never use them for text.

Never write `Color(0x...)`, `Color.fromARGB`, `Color.fromRGBO` or `Colors.<name>` outside `packages/eschool_core/lib/src/theme/`. `Colors.transparent` is the one exception. `test/theme_colors_test.dart` in each app and in the shared package fails the build on any other. Where there is no `BuildContext` (an Android notification's accent colour, for example), use `BrandColors` directly.

To lighten a role, use `.withValues(alpha: x)` on it rather than a new colour.

## Components

Use Material 3 widgets, or a shared widget built on them:

| Need | Use |
|---|---|
| Main action | `CustomRoundedButton` (a `FilledButton` with the apps' sizes) or `FilledButton` |
| Secondary action | `OutlinedButton`, or `CustomRoundedButton` with `showBorder: true` |
| Low-emphasis action | `TextButton` |
| Text input | `TextField` / `TextFormField`. The theme gives it the outlined border. |
| On/off | `Switch` |
| Several choices | `Checkbox`, `Radio`, or `SegmentedButton` |
| Icon action | `IconButton`, or `SvgButton` for the apps' SVG icons. Give it a `tooltip`; back buttons use `MaterialLocalizations.of(context).backButtonTooltip`. |
| Floating action | `FloatingActionButton`. The theme colours it with `primary`. |
| Confirmation | `AlertDialog`, with `TextButton` actions |
| Sheet | `UiUtils.showBottomSheet` (wraps `showModalBottomSheet`) |
| Loading | `CustomCircularProgressIndicator` or `CircularProgressIndicator` |

Don't build a button out of `InkWell` or `GestureDetector` around a `Container`. It has no button semantics for screen readers, no keyboard focus, and no pressed or disabled states.

Shapes come from the theme: buttons, fields and cards use `AppTheme.radius`, and bottom sheets use `AppTheme.bottomSheetRadius`. Use those constants when a custom container has to match.

Text uses Poppins through the theme's text theme. For new code, start from `Theme.of(context).textTheme` (for example `textTheme.titleMedium`) rather than a bare `TextStyle(fontSize: ...)`.

When a widget is the same in both apps, move it into `packages/eschool_core` (see the package [README](https://github.com/ShawnDaPrawn/Eschool/blob/main/packages/eschool_core/README.md)), so it is fixed and themed once.

## Changing the brand

1. Edit `packages/eschool_core/lib/src/theme/brand_colors.dart`.
2. In `packages/eschool_core`, run `flutter test`. `theme_test.dart` checks that text stays readable (WCAG AA, 4.5:1) on every main pair, including each status colour and the accent's container. The tertiary and status text colours adjust themselves; if the primary fails, choose a darker shade of it rather than lowering the bar.
3. Run `flutter test --update-goldens test/goldens` in the same folder, open `test/goldens/components.png`, and check it before committing.
4. Run `flutter analyze --no-fatal-infos` and `flutter test` in both apps.

## Screenshot tests

`packages/eschool_core/test/goldens/components_test.dart` draws the colour scheme, the status colours, the Material components and the shared widgets, and compares the picture with `components.png` pixel for pixel. Text is drawn in Flutter's test font (blocks) and shadows as solid outlines, so the picture checks colour, shape and size rather than typography. CI runs it on Linux with Flutter 3.44.9. Regenerate the image on the same setup, or the comparison fails on font rendering differences.

## See also

- [Mobile-Apps-Overview](Mobile-Apps-Overview)
- [Mobile-App-Configuration](Mobile-App-Configuration#theme-colours)
- [Testing-and-CI](Testing-and-CI#flutter-tests)
- [Contributing](Contributing#flutter-apps-dart)
