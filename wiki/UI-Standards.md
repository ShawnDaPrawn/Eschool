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
| [`m3-base.css`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/public/assets/css/m3-base.css) | Poppins (`public/assets/fonts/poppins`, OFL), the old font names (`Roboto-Medium` and so on) mapped to Poppins, the status text colours `--app-status-<name>-text`, Bootstrap's colour utilities (`.text-danger`, `.bg-light`, …) on the tokens, and the legacy `--theme-color` / `--secondary-color` variables the template bundles still read, pointed at the tokens. |
| [`m3-components.css`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/public/assets/css/m3-components.css) | The admin panel's Material 3 components, restyling the template's Bootstrap 4 markup in place: buttons, icon buttons, text fields, selects and Select2, checkboxes, radios and switches, tabs, dialogs (Bootstrap modals and SweetAlert2 confirms), menus, tooltips, snackbars (jquery-toast), progress, lists, pagination and alerts. The website no longer uses it: it is built on the Material 3 library (below), with its own layout in `site.css`. |

Load order, in `layouts/include.blade.php` (admin panel, login, password pages) and the error pages: `m3-tokens.css` first, then the template's own CSS (`style.css` or `rtl.css`, Bootstrap), then `m3-base.css`, then (admin panel) `m3-components.css`, then the page stylesheets (`sidebar.css`, `header.css`, `content.css`, `tables.css`, `dashboard.css` and the per-page ones). The public website (`web/master.blade.php`) loads `m3-tokens.css`, `m3/m3.css` and `site.css` (see below). A page stylesheet loads after the components, so it can refine one for its screen; it shouldn't redraw one (its own button radius, a Bootstrap glow ring) unless the design calls for it.

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

The web components copy the apps' Flutter theme: Material 3's defaults, with a 10px corner (`AppTheme.radius`, CSS `var(--app-radius)`) on buttons, text fields and cards. Use the existing Bootstrap and template classes; `m3-components.css` gives them the M3 look, so a view rarely needs component CSS of its own.

| Component | Classes | Material 3 look |
|---|---|---|
| Filled button (main action) | `.btn-theme`, `.btn-primary` | 40px high, padding 0 24px, 10px corners, label-large; primary / on-primary; level-1 shadow on hover |
| Destructive filled button | `.btn-danger` | error / on-error |
| Tonal button | `.btn-light`, `.btn-inverse-*` | secondary-container / on-secondary-container |
| Outlined button (secondary action) | `.btn-secondary`, `.btn-outline-*` | 1px outline, primary text |
| Text button | `.btn-link` | primary text, padding 0 12px |
| Small / large button | `.btn-sm` (32px), `.btn-lg` (48px) | |
| Icon button | `.btn-icon`, `.close` | 40px circle; in tables, a tinted (tonal) circle by intent: edit primary, delete error |
| Text field | `.form-control`, `select.form-control`, Select2 | 48px, 1px outline, 10px corners, body-large; 2px primary outline on focus, error outline when invalid; label above in body-medium 500 |
| Search | the bootstrap-table search | M3 search bar: 48px, fully rounded, surface-container-high, leading magnifier |
| Checkbox / radio | template `.form-check` + `.input-helper`, or a native input | 18px box with 2px corners / 20px ring; primary when checked; 40px circular state layer |
| Switch | `.custom-switch` | 52×32 track, 16px thumb growing to 24px when on |
| Tabs | `.nav-tabs` | primary tabs: 48px, title-small, primary text and a 3px indicator on the active tab, a divider under the bar |
| Card | `.card`, `.dash-card` | outlined: surface-container-lowest, 1px outline-variant, 10px corners, no shadow |
| Dialog | `.modal`, SweetAlert2 | surface-container-high, 28px corners, 24px padding, headline-small title, no header or footer rules, actions at the end (cancel, then confirm) |
| Menu | `.dropdown-menu`, Select2 results, table menus | surface-container, 4px corners, level-2 shadow, 48px items, selected item in secondary-container |
| Snackbar | `showSuccessToast()` / `showErrorToast()` | inverse-surface, 4px corners, bottom centre, status icon and countdown bar |
| Tooltip | Bootstrap tooltip | inverse-surface, 4px corners, body-small, no arrow |
| Chip / status label | `.badge-*`, Select2 choices | 24px label chip with 8px corners in the status tint; Select2 choices are 32px input chips |
| Progress | `.progress` | 4px, primary on secondary-container |
| Pagination | `.pagination`, table pages | 40px circular page buttons, current page in secondary-container |
| Navigation drawer | `.sidebar` | Dark Navy, pill-shaped items, the active item filled with primary |
| List | `.list-group`, dashboard lists | rows split by outline-variant dividers, a 40px round leading icon or avatar |
| Empty state | `.dash-empty` | an icon in a tonal circle above the message |
| Alert | `.alert-*`, `.note-banner-*` | a tonal container in the status colour, 12px corners, no border |

State layers follow M3: the content colour at 8% on hover and 10% on focus and press, drawn over the component; disabled content at 38% on a 12% container. Keyboard focus is a 3px `secondary` outline, never Bootstrap's glow ring.

Text is Poppins, sized from the type scale: page headings `--md-sys-typescale-headline-small-size`, card titles `title-medium`, body `body-medium`, labels and buttons `label-large`.

### The Material 3 web library (replacing Bootstrap)

The admin panel and the website are moving off Bootstrap entirely, onto our own Material 3 library in `admin-panel/public/assets/m3/`. It has no third-party UI framework, and its components are the apps' Flutter components. Until a page is migrated it keeps its Bootstrap markup, restyled by `m3-components.css` (the table above).

| File | What it holds |
|---|---|
| [`m3/m3.css`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/public/assets/m3/m3.css) | Base styles, the type scale, surfaces, the layout grid and every component, on the tokens only. |
| [`m3/m3.js`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/public/assets/m3/m3.js) | Dialogs, menus, tabs, snackbars, confirm dialogs and the scrolling top app bar. No dependencies; it wires itself up from `data-m3-*` attributes. |

A migrated page loads `css/m3-tokens.css`, then `m3/m3.css`, then its own stylesheet, and `m3/m3.js` before its scripts; its `<html>` or `<body>` has the class `m3`. It loads no Bootstrap CSS or JS, no `vendor.bundle.base`, and none of the template's `style.css` / `rtl.css`.

Class names are `m3-<component>`, `m3-<component>__<part>` and `m3-<component>--<variant>`. Layout uses logical properties (`margin-inline-start` and so on), so right-to-left pages need no extra rules.

**Layout.** `.m3-page` centres the content with M3's margins (16px, 24px from 600px wide). `.m3-section` adds vertical padding. `.m3-grid` is a 12-column grid: children span all 12 columns unless they have `.m3-col-N`, which can vary by M3 window class: `.m3-col-medium-N` (600px and up), `.m3-col-expanded-N` (840px), `.m3-col-large-N` (1200px). `.m3-row` (wrapping flex row) and `.m3-stack` (flex column) space their children with `.m3-gap-N` (N × 4px). The surface classes (`.m3-surface-container-low` and so on) set a background role.

**Components.**

| Bootstrap | M3 library |
|---|---|
| `.container`, `.row`, `.col-md-6` | `.m3-page`, `.m3-grid`, `.m3-col-12 .m3-col-expanded-6` |
| `.d-flex`, `.justify-content-*`, `.mb-3` | `.m3-row` / `.m3-stack` with `.m3-gap-N`, `.m3-justify-*` |
| `.btn .btn-theme` / `.btn-primary` | `.m3-button .m3-button--filled` |
| `.btn-secondary`, `.btn-outline-*` | `.m3-button--outlined` |
| `.btn-light` | `.m3-button--tonal` |
| `.btn-link` | `.m3-button--text` |
| `.btn-danger` | `.m3-button--filled .m3-button--error` |
| `.btn-icon` | `.m3-icon-button` (`--filled`, `--tonal`, `--outlined`, `--error`) |
| `.form-group` + `label` + `.form-control` | `<label class="m3-text-field"><input class="m3-text-field__input" placeholder=" "><span class="m3-text-field__label">Name</span></label>`: the label floats on the outline. `.m3-text-field--select` for a `<select>`, `.m3-text-field__supporting` for helper or error text. jQuery Validate's error labels are styled. |
| `.form-check` + `.input-helper` | `.m3-checkbox`, `.m3-radio`, `.m3-switch` (a real input inside the label, then `__control` or `__track`) |
| `.card`, `.card-body`, `.card-title` | `.m3-card` (`--outlined`, `--filled`, `--interactive`), `__media`, `__content`, `__headline`, `__supporting`, `__actions` |
| `.modal` and `data-toggle="modal"` | `<dialog class="m3-dialog">` with `__headline`, `__content`, `__actions`; open it with `data-m3-dialog-open="id"` or `M3.dialog.open(el)`, close it with `data-m3-dialog-close` or `<form method="dialog">` |
| `.dropdown-menu` and `data-toggle="dropdown"` | `<div class="m3-menu" id="…" popover role="menu">` with `.m3-menu__item`, opened by `data-m3-menu="id"` |
| `.nav-tabs` | `.m3-tabs[role=tablist]` with `.m3-tab[role=tab][aria-controls]`; the panels switch themselves |
| `.collapse`, accordion | `<details class="m3-expansion"><summary class="m3-expansion__header">` |
| `.navbar` | `.m3-top-app-bar` (add `data-m3-scroll` to tint it on scroll) |
| offcanvas, the sidebar | `.m3-navigation-drawer` (`--inverse` for Dark Navy); a `<dialog>` for a modal drawer |
| `.badge-*` | `.m3-chip--status-success` (and `-warning`, `-danger`, `-info`, `-neutral`); `.m3-badge` for counts |
| `.alert-*` | `.m3-banner--success` and so on |
| `.list-group` | `.m3-list` (`--divided`) with `.m3-list__item`, `__leading`, `__content`, `__headline`, `__supporting`, `__trailing` |
| `.pagination` | `.m3-pagination` with `.m3-pagination__item[aria-current=page]` |
| `.progress` | `.m3-progress-linear` (set `--m3-progress`), `.m3-progress-circular` |
| jquery-toast | `M3.snackbar({ message, type: 'success' })` |
| SweetAlert2 confirm | `M3.confirm({ title, message, confirmText, destructive: true }).then(ok => …)` |
| tooltip plugin | `data-m3-tooltip="Text"` |

**No new Bootstrap.** `tests/Unit/BootstrapFreeTest.php` scans the views, `app/` and the page scripts for Bootstrap classes, `data-toggle` / `data-bs-*` attributes, Bootstrap's jQuery plugins and Bootstrap assets. `tests/bootstrap-baseline.txt` lists the files that still use it. The test fails when a file not on the list uses Bootstrap, and when a file on the list no longer does, so the list only shrinks. When you rewrite a page, build it with the library and take it off the list. `UPDATE_BOOTSTRAP_BASELINE=1 vendor/bin/phpunit tests/Unit/BootstrapFreeTest.php` removes finished files; it never adds any.

### Admin panel page pattern

Since Phase 0 every new panel page follows one pattern, so a school admin needs no training: a header with the page title and one primary action, filters, a table with bulk actions, a designed empty state that says what to do first, form cards that validate inline and save without a reload, and one confirm dialog. The pieces are Blade components in [`resources/views/components/page/`](https://github.com/ShawnDaPrawn/Eschool/tree/main/admin-panel/resources/views/components/page), styled by `public/assets/css/panel.css` and driven by `public/assets/js/custom/page.js` (both loaded by `layouts/master` on every panel page). Home, global search and the new-year wizard use it. Existing pages keep their markup and keep working; move a page onto the pattern when a phase touches it.

| Component | Use it for | Notes |
|---|---|---|
| `<x-page.header>` | The top of every page | `title` (the page's only `<h1>`), optional `subtitle`, and at most one primary action (`primary-label`, `primary-href`, `primary-icon`, `primary-permission`). Quieter actions go in the `actions` slot. Shows "section / group" from the navigation registry above the title. |
| `<x-page.filters id="...">` | Filter fields above a list | Give the table `toolbar="#id"` so bootstrap-table puts its search box beside them. A field with `data-filter="table_id"` reloads that table when it changes. |
| `<x-page.table>` | Every server-side list | Wraps bootstrap-table with the panel's conventions: `data-escape="true"`, server pagination, `data-query-params="queryParams"`, page sizes 10/25/50/100, refresh. Columns go in the `columns` slot; a `<th data-field="state" data-checkbox="true">` column plus a `bulk` slot of buttons with `data-bulk-action="url"` (and `data-bulk-confirm="question"`) gives bulk actions: page.js posts the ticked rows' `ids[]` and refreshes. `empty-title`, `empty-message`, `empty-action-label`, `empty-action-href` design the empty state; a search with no match shows "Nothing matches" instead. page.js starts the table, so don't add `data-toggle="table"`. |
| `<x-page.empty-state>` | A page or card with nothing in it yet | An icon in a tonal circle, a short title, one sentence that says what to do first, and the action that does it. |
| `<x-page.form-card>` | Any form that saves | Posts with `fetch` and the CSRF token, never reloads on an error, and keeps a draft in `sessionStorage` until the save works (passwords and files are never kept), so an expired session or a closed tab loses nothing. Props: `action`, `method`, `title`, `submit-label`, `redirect`, `reset-on-success`, `refresh-table`. |
| `<x-page.field>` | One labelled field | `name`, `label`, `type` (any input type, `select` with `options`, or `textarea`), `help`, `required`. Renders `label for`, `aria-describedby` for the hint and the error, and the error box page.js fills. Extra attributes (`min`, `max`, `maxlength`, `pattern`) go on the control and are checked as people type. |
| `<x-page.confirm-dialog>` | Confirming an action | One per page, already in `layouts/master`. Add `data-confirm="question"` (and `data-confirm-title`, `data-confirm-label`, `data-confirm-danger`) to a button or link, or call `pageConfirm({title, message, confirmLabel, danger})`, which returns a promise of `true` or `false`. Focus moves into the dialog and back; Escape cancels. |

A form card's endpoint answers JSON. On success: `{"error": false, "message": "...", "redirect": "optional url", "data": {...}}`; page.js shows the message, then goes to `redirect`, resets the form, refreshes a table, or fires a `page-form:saved` event for the page's own script. On a validation error: HTTP 422 with Laravel's `{"message": "...", "errors": {"field": ["..."]}}` (a FormRequest does this); each message appears under its field (`terms.0.name` maps to `terms[0][name]`), the first field gets focus and a summary appears at the top of the form. Anything else (`{"error": true, "message": "..."}`, a 500, no network): the message appears and nothing typed is lost.

A list page on the pattern:

```blade
@extends('layouts.master')
@section('title') {{ __('nav_notices') }} @endsection
@section('content')
<div class="content-wrapper">
    <x-page.header :title="__('nav_notices')" :primary-label="__('send_a_notice')"
        primary-href="#notice-form" primary-permission="announcement-create" />

    <x-page.form-card id="notice-form" :action="url('announcement')" :title="__('send_a_notice')"
        :submit-label="__('send')" reset-on-success refresh-table="table_list">
        <x-page.field name="title" :label="__('title')" required maxlength="255" />
        <x-page.field name="description" type="textarea" :label="__('description')" required />
    </x-page.form-card>

    <x-page.filters id="notice-filters">
        <x-page.field name="filter_class_id" type="select" :label="__('grade')" :options="$grades"
            :placeholder="__('all')" data-filter="table_list" />
    </x-page.filters>
    <x-page.table :url="url('announcement-list')" toolbar="#notice-filters" empty-icon="announcement"
        :empty-title="__('no_notices_yet')" :empty-message="__('no_notices_yet_hint')"
        :empty-action-label="__('send_a_notice')" empty-action-href="#notice-form">
        <x-slot:columns>
            <th data-field="title" data-sortable="true">{{ __('title') }}</th>
            <th data-field="operate" data-escape="false">{{ __('action') }}</th>
        </x-slot:columns>
    </x-page.table>
</div>
@endsection
```

Rules for every panel page, old or new:

- **Navigation.** Add the page to [`config/navigation.php`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/config/navigation.php), not to a sidebar file: pick the section by the job people do (Home, People, Academics, Communication, Money, Activities, Wellbeing, Settings), a group, a `label` key in plain words, the route, the `permissions` that show it, the `module` key Phase 1 will switch (or `null`) and `keywords` for search. `NavigationTest` fails on an item without a route, permissions or module key, and on a label missing from a language file. The item is shown only to people with one of its permissions; the page must still check its own permission.
- **Plain words.** "Learners", "Homerooms", "Terms", "Grades", "School years", not table names (students, class sections, semesters, classes, session years). Every new label goes into every file in `resources/lang/` (English and Arabic today).
- **Escaping.** Tables keep `data-escape="true"`; only a column that returns HTML built with `escapeHtml()` and `safeUrl()` sets `data-escape="false"`. Blade output uses `{{ }}`.
- **Read-only years.** When the header's year switcher shows a year other than the current one, the server refuses writes (`EnforceReadOnlyYears`, allow-list in `config/panel.php`) and `panel.js` disables POST forms' submit buttons, delete buttons and anything marked `data-write`, and stops jQuery writes before they leave. Mark a control `data-write` if it changes year-scoped data without a form; mark a form `data-year-safe` only if its endpoint is on the allow-list.
- **Keyboard and screen readers.** Every control is a real `<button>`, `<a>` or form field with a visible label or `aria-label`; focus is always visible (a 3px `secondary` outline); dialogs trap focus and return it. `layouts/master` provides a skip link and a `<main>` landmark; a page has exactly one `<h1>`.
- **Width.** Every page works at 1024 px (a school laptop) and 1280 px with no horizontal scroll; wide tables scroll inside their card. Playwright checks this and runs axe on every panel page (see [Testing-and-CI](Testing-and-CI#playwright-admin-panel)).

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
