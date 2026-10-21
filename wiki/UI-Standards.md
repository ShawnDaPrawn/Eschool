The rules every screen in the two Flutter apps, the admin panel and the public website follows. Both apps use one Material 3 theme in the colours of the Navigate Wealth design system, NavigateEd's sister platform, defined once in the shared package [`packages/eschool_core`](https://github.com/ShawnDaPrawn/Eschool/tree/main/packages/eschool_core/lib/src/theme). Colours, shapes and type come from that theme, so the brand can change in one file and every screen follows. Tests in each app fail when a colour is hard-coded, and a screenshot test in the shared package fails when the theme changes how the shared widgets look. The admin panel and the website use the same theme as CSS design tokens generated from it; see [Admin panel and website](#admin-panel-and-website).

## Where the theme lives

| File | What it holds |
|---|---|
| [`brand_colors.dart`](https://github.com/ShawnDaPrawn/Eschool/blob/main/packages/eschool_core/lib/src/theme/brand_colors.dart) | `BrandColors`: the Navigate Wealth colour tokens (Primary Purple, Dark Navy, White, Light Gray, Black, Muted Gray, Border Gray and the status colours). The only place to change the brand. |
| [`app_theme.dart`](https://github.com/ShawnDaPrawn/Eschool/blob/main/packages/eschool_core/lib/src/theme/app_theme.dart) | `AppTheme.light()`: the `ThemeData` both apps pass to `MaterialApp`. Builds the colour scheme: the primary, accent and error roles are tonal palettes from `ColorScheme.fromSeed(dynamicSchemeVariant: DynamicSchemeVariant.fidelity)`, which keeps the brand colours exact; the backgrounds, text colours and borders are the design system's own tokens. Sets the corner radii (`AppTheme.radius` 10, `AppTheme.bottomSheetRadius` 20). |
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
| Main text and icons | `onSurface` (Black) |
| Secondary text, hints, captions | `onSurfaceVariant` (Muted Gray) |
| Page, sheet and dialog backgrounds | `surface` (White) |
| Grey fills: fields, chips, cards on a page | `surfaceContainerLow` (Light Gray; lighter: `surfaceContainerLowest`; darker: `surfaceContainer`, `surfaceContainerHigh`, `surfaceContainerHighest`) |
| Borders and dividers | `outlineVariant` (Border Gray); `outline` for control borders that must stand out (text fields) |
| Buttons, links, selected and active states, small brand icons | `primary` (Primary Purple) |
| Text and icons on `primary` | `onPrimary` |
| Screen headers and other dark sections | `inverseSurface` (Dark Navy), with `onInverseSurface` on top |
| Accent fills: chips, highlights, tints | `tertiaryContainer` (Dark Navy), with `onTertiaryContainer` (white) on top |
| Accent text, icons and borders | `tertiary` (a darker navy) |
| Errors and destructive actions | `error`, with `onError` on top |
| Present, paid, approved | `StatusColors.of(context).success` |
| Pending, due soon | `.warning` |
| Absent, rejected, overdue | `.danger` |
| Neutral information | `.info` |
| Text on a status colour | that status's own: `.onSuccess`, `.onWarning`, `.onDanger`, `.onInfo` |
| Text on a colour from the server, or a background chosen at runtime | `colorScheme.readableOn(background)` |
| Content on video, photos or a scrim | `onInverseSurface` |
| Shadows | `shadow` |
| Dimming behind a dialog | `scrim` |

`secondary` and its relatives are generated from the purple and not used by the apps; don't use them for text.

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

## Admin panel and website

The admin panel and the public website are Blade pages, not Flutter, but they use the same Material 3 theme: the same colour roles, type scale and shapes, as CSS custom properties.

| File | What it holds |
|---|---|
| [`m3-tokens.css`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/public/assets/css/m3-tokens.css) | The design tokens: `--md-sys-color-*` (every colour role of `AppTheme.light()`), `--app-status-*` and `--app-status-on-*` (`StatusColors`), state-layer opacities, `--md-sys-shape-corner-*`, `--md-sys-elevation-level0` to `5`, the type scale `--md-sys-typescale-<role>-size/line-height/weight/tracking`, and `--md-ref-typeface-plain` (Poppins). Generated; don't edit it. `packages/eschool_core/test/web_tokens_test.dart` writes it from the Dart theme and fails CI when the committed file differs. |
| [`m3-base.css`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/public/assets/css/m3-base.css) | Poppins (`public/assets/fonts/poppins`, OFL), the old font names (`Roboto-Medium` and so on) mapped to Poppins, the status text colours `--app-status-<name>-text`, and the legacy `--theme-color` / `--secondary-color` variables the template bundles still read, pointed at the tokens. |

Load order, in `layouts/include.blade.php` (admin panel, login, password pages), `web/header.blade.php` (website) and the error pages: `m3-tokens.css` first, then the template's own CSS (`style.css` or `rtl.css`, Bootstrap), then `m3-base.css`, then the page stylesheets (`sidebar.css`, `header.css`, `content.css`, `tables.css`, `dashboard.css`, `webstyle.css` and the per-page ones).

The `theme_color` and `secondary_color` settings no longer colour the panel or the website. They are still used, and still editable on General Settings, for the printed certificates and result sheets.

### Colours

Use the token for the same role as in the apps' table above: `--md-sys-color-on-surface` for main text, `--md-sys-color-on-surface-variant` for secondary text, `--md-sys-color-surface-container-low` for the page, `--md-sys-color-surface-container-lowest` for cards, `--md-sys-color-outline-variant` for borders, `--md-sys-color-primary` for actions and selection, `--md-sys-color-inverse-surface` (Dark Navy) for the drawer and dark sections, `--md-sys-color-error` for errors and destructive actions. In CSS:

| You are colouring | Use |
|---|---|
| A status fill: a dot, a bar, a solid badge | `var(--app-status-success)` (or `-warning`, `-danger`, `-info`), with `var(--app-status-on-success)` on top |
| Status text or an icon on a light surface | `var(--app-status-success-text)` and so on. The raw status colours are too faint as text (warning is 1.9:1 on white); these pass 4.5:1. |
| A pale status or brand background | `color-mix(in srgb, var(--app-status-warning) 14%, var(--md-sys-color-surface-container-lowest))` |
| Hover, focus and pressed states | the content colour over the component at `var(--md-sys-state-hover-opacity)` (8%), `-focus-opacity` or `-pressed-opacity`, e.g. `color-mix(in srgb, var(--md-sys-color-primary) var(--md-sys-state-hover-opacity), transparent)` |
| Disabled | content at `var(--md-sys-state-disabled-content-opacity)`, container at `-container-opacity` |
| Charts (ApexCharts) | read the tokens in JavaScript: `getComputedStyle(document.documentElement).getPropertyValue('--md-sys-color-primary')`, as `public/assets/js/custom/dashboard.js` does |

Never write a hex, `rgb()`, `hsl()` or named colour in a stylesheet. `tests/Unit/DesignTokensTest.php` reads every stylesheet in `public/assets/css` (except the generated tokens, the legacy `style.css` / `rtl.css` / `vendor.bundle.base.css` and third-party CSS) and fails on any raw colour in a declaration. Two exceptions:

- A colour inside a `data:` URI can't read a CSS variable. Prefer a mask (`mask: url(...)` with `background-color: var(--token)`), which ignores the SVG's own colour. Where a mask can't work (a form control has no pseudo-elements), write the token's value and name the token in a comment.
- Something that must keep its own colours, such as the live preview of the printed ID card, goes between `/* design-tokens: off */` and `/* design-tokens: on */` comments, with a reason.

Inline `style=""` colours in Blade views aren't checked yet; use the tokens there too.

### Components

| Need | CSS |
|---|---|
| Card | Outlined: `surface-container-lowest`, a 1px `outline-variant` border, `--md-sys-shape-corner-medium`, no shadow and no lift on hover (`.card`, `.dash-card`). Dialogs and menus are the raised surfaces (`--md-sys-elevation-level5` and `level2`). |
| Main action | `.btn-primary` / `.btn-theme`: primary fill, on-primary text |
| Secondary action | `.btn-secondary`: outlined, primary text |
| Navigation drawer | `.sidebar`: Dark Navy, pill-shaped items, the active item filled with primary |
| Lists inside a card | rows separated by `outline-variant` dividers, not boxed one by one; a 40px round leading icon or avatar |
| Empty state | `.dash-empty`: an icon in a tonal circle above the message |
| Text | Poppins, sizes from the type scale: titles `--md-sys-typescale-title-medium-size`, body `--md-sys-typescale-body-medium-size`, labels `--md-sys-typescale-label-large-size`, page headings `--md-sys-typescale-headline-small-size` |

## Changing the brand

1. Edit `packages/eschool_core/lib/src/theme/brand_colors.dart`.
2. In `packages/eschool_core`, run `flutter test`. `theme_test.dart` checks that the scheme uses the tokens exactly and that text stays readable (WCAG AA, 4.5:1) on every main pair, including the header, each status colour and the accent's container. The status text colours adjust themselves; if another pair fails, choose a darker shade of the colour rather than lowering the bar.
3. Run `flutter test --update-goldens test/goldens` in the same folder, open `test/goldens/components.png`, and check it before committing.
4. Run `flutter analyze --no-fatal-infos` and `flutter test` in both apps.
5. Regenerate the web tokens: in `packages/eschool_core`, run `UPDATE_WEB_TOKENS=1 flutter test test/web_tokens_test.dart` and commit `admin-panel/public/assets/css/m3-tokens.css`. If on-surface-variant or on-primary changed, also update the few icons written out inside `data:` URIs (search the stylesheets for `%23`).

## Screenshot tests

`packages/eschool_core/test/goldens/components_test.dart` draws the colour scheme, the status colours, the Material components and the shared widgets, and compares the picture with `components.png` pixel for pixel. Text is drawn in Flutter's test font (blocks) and shadows as solid outlines, so the picture checks colour, shape and size rather than typography. CI runs it on Linux with Flutter 3.44.9. Regenerate the image on the same setup, or the comparison fails on font rendering differences.

## See also

- [Mobile-Apps-Overview](Mobile-Apps-Overview)
- [Admin-Panel-Overview](Admin-Panel-Overview)
- [Public-Website](Public-Website)
- [Mobile-App-Configuration](Mobile-App-Configuration#theme-colours)
- [Testing-and-CI](Testing-and-CI#flutter-tests)
- [Contributing](Contributing#flutter-apps-dart)
