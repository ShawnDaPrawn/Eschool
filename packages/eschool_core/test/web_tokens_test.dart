import 'dart:io';

import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

// The admin panel and the public website use the same Material 3 design
// tokens as the apps, as CSS custom properties in
// admin-panel/public/assets/css/m3-tokens.css. This test generates that file
// from AppTheme and fails when the committed file differs, so the web can't
// drift from the apps. After changing the theme, run
//   UPDATE_WEB_TOKENS=1 flutter test test/web_tokens_test.dart
// and commit the new CSS.
const tokensPath = '../../admin-panel/public/assets/css/m3-tokens.css';

void main() {
  test('the web tokens match the app theme', () {
    final css = webTokensCss();
    final file = File(tokensPath);
    if (Platform.environment['UPDATE_WEB_TOKENS'] == '1') {
      file.writeAsStringSync(css);
    }
    expect(
      file.existsSync() ? file.readAsStringSync() : '',
      css,
      reason:
          'm3-tokens.css is out of date. Run '
          'UPDATE_WEB_TOKENS=1 flutter test test/web_tokens_test.dart',
    );
  });
}

String webTokensCss() {
  final theme = AppTheme.light();
  final scheme = theme.colorScheme;
  final status = StatusColors.from(scheme);
  final out = StringBuffer()
    ..writeln('/*')
    ..writeln(' * Material 3 design tokens for the admin panel and website.')
    ..writeln(' *')
    ..writeln(' * Generated from the apps\' theme (packages/eschool_core) by')
    ..writeln(
      ' * packages/eschool_core/test/web_tokens_test.dart. Do not edit;',
    )
    ..writeln(
      ' * change brand_colors.dart or app_theme.dart and regenerate with',
    )
    ..writeln(' *   UPDATE_WEB_TOKENS=1 flutter test test/web_tokens_test.dart')
    ..writeln(' */')
    ..writeln(':root {');

  void section(String name) => out.writeln('\n    /* $name */');
  void token(String name, String value) => out.writeln('    --$name: $value;');

  section('Colour roles');
  final roles = <String, Color>{
    'primary': scheme.primary,
    'on-primary': scheme.onPrimary,
    'primary-container': scheme.primaryContainer,
    'on-primary-container': scheme.onPrimaryContainer,
    'inverse-primary': scheme.inversePrimary,
    'secondary': scheme.secondary,
    'on-secondary': scheme.onSecondary,
    'secondary-container': scheme.secondaryContainer,
    'on-secondary-container': scheme.onSecondaryContainer,
    'tertiary': scheme.tertiary,
    'on-tertiary': scheme.onTertiary,
    'tertiary-container': scheme.tertiaryContainer,
    'on-tertiary-container': scheme.onTertiaryContainer,
    'error': scheme.error,
    'on-error': scheme.onError,
    'error-container': scheme.errorContainer,
    'on-error-container': scheme.onErrorContainer,
    'surface': scheme.surface,
    'on-surface': scheme.onSurface,
    'on-surface-variant': scheme.onSurfaceVariant,
    'surface-dim': scheme.surfaceDim,
    'surface-bright': scheme.surfaceBright,
    'surface-container-lowest': scheme.surfaceContainerLowest,
    'surface-container-low': scheme.surfaceContainerLow,
    'surface-container': scheme.surfaceContainer,
    'surface-container-high': scheme.surfaceContainerHigh,
    'surface-container-highest': scheme.surfaceContainerHighest,
    'inverse-surface': scheme.inverseSurface,
    'on-inverse-surface': scheme.onInverseSurface,
    'outline': scheme.outline,
    'outline-variant': scheme.outlineVariant,
    'shadow': scheme.shadow,
    'scrim': scheme.scrim,
  };
  roles.forEach((name, color) => token('md-sys-color-$name', css(color)));

  section('Status colours (StatusColors), each with its own text colour');
  final statuses = <String, (Color, Color)>{
    'success': (status.success, status.onSuccess),
    'warning': (status.warning, status.onWarning),
    'danger': (status.danger, status.onDanger),
    'info': (status.info, status.onInfo),
  };
  statuses.forEach((name, pair) {
    token('app-status-$name', css(pair.$1));
    token('app-status-on-$name', css(pair.$2));
  });

  section('State layers: opacity of the content colour over a component');
  token('md-sys-state-hover-opacity', '8%');
  token('md-sys-state-focus-opacity', '10%');
  token('md-sys-state-pressed-opacity', '10%');
  token('md-sys-state-disabled-content-opacity', '38%');
  token('md-sys-state-disabled-container-opacity', '12%');

  section('Shape');
  token('md-sys-shape-corner-extra-small', '4px');
  token('md-sys-shape-corner-small', '8px');
  token('md-sys-shape-corner-medium', '12px');
  token('md-sys-shape-corner-large', '16px');
  token('md-sys-shape-corner-extra-large', '28px');
  token('md-sys-shape-corner-full', '9999px');
  token('app-radius', '${px(AppTheme.radius)}px');
  token('app-radius-sheet', '${px(AppTheme.bottomSheetRadius)}px');

  section('Elevation');
  const shadows = [
    'none',
    '0 1px 2px 0 rgba(0, 0, 0, 0.3), 0 1px 3px 1px rgba(0, 0, 0, 0.15)',
    '0 1px 2px 0 rgba(0, 0, 0, 0.3), 0 2px 6px 2px rgba(0, 0, 0, 0.15)',
    '0 1px 3px 0 rgba(0, 0, 0, 0.3), 0 4px 8px 3px rgba(0, 0, 0, 0.15)',
    '0 2px 3px 0 rgba(0, 0, 0, 0.3), 0 6px 10px 4px rgba(0, 0, 0, 0.15)',
    '0 4px 4px 0 rgba(0, 0, 0, 0.3), 0 8px 12px 6px rgba(0, 0, 0, 0.15)',
  ];
  for (var level = 0; level < shadows.length; level++) {
    token('md-sys-elevation-level$level', shadows[level]);
  }

  section('Type scale (the apps\' Material 3 typography, in Poppins)');
  token(
    'md-ref-typeface-plain',
    "'Poppins', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif",
  );
  // Sizes, line heights and tracking of the apps' Material 3 type scale.
  const geometry = Typography.englishLike2021;
  final styles = <String, TextStyle?>{
    'display-large': geometry.displayLarge,
    'display-medium': geometry.displayMedium,
    'display-small': geometry.displaySmall,
    'headline-large': geometry.headlineLarge,
    'headline-medium': geometry.headlineMedium,
    'headline-small': geometry.headlineSmall,
    'title-large': geometry.titleLarge,
    'title-medium': geometry.titleMedium,
    'title-small': geometry.titleSmall,
    'body-large': geometry.bodyLarge,
    'body-medium': geometry.bodyMedium,
    'body-small': geometry.bodySmall,
    'label-large': geometry.labelLarge,
    'label-medium': geometry.labelMedium,
    'label-small': geometry.labelSmall,
  };
  styles.forEach((name, style) {
    final size = style!.fontSize!;
    token('md-sys-typescale-$name-size', '${px(size)}px');
    token(
      'md-sys-typescale-$name-line-height',
      '${(size * style.height!).round()}px',
    );
    token('md-sys-typescale-$name-weight', '${style.fontWeight!.value}');
    token('md-sys-typescale-$name-tracking', '${px(style.letterSpacing!)}px');
  });

  out.writeln('}');
  return out.toString();
}

String css(Color color) {
  final argb = color.toARGB32();
  final r = (argb >> 16) & 0xff;
  final g = (argb >> 8) & 0xff;
  final b = argb & 0xff;
  final a = (argb >> 24) & 0xff;
  if (a == 0xff) {
    return '#${(argb & 0xffffff).toRadixString(16).padLeft(6, '0')}';
  }
  return 'rgba($r, $g, $b, ${(a / 255).toStringAsFixed(2)})';
}

// Whole numbers without a trailing ".0"; others to two decimals.
String px(double value) {
  final rounded = (value * 100).round() / 100;
  return rounded == rounded.roundToDouble()
      ? rounded.toInt().toString()
      : rounded.toString();
}
