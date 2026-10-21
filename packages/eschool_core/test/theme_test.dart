import 'dart:math' as math;

import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

// WCAG relative luminance contrast ratio.
double contrast(Color a, Color b) {
  final la = a.computeLuminance();
  final lb = b.computeLuminance();
  return (math.max(la, lb) + 0.05) / (math.min(la, lb) + 0.05);
}

void main() {
  final theme = AppTheme.light();
  final scheme = theme.colorScheme;

  test('is a Material 3 theme', () {
    expect(theme.useMaterial3, isTrue);
  });

  test('keeps the brand colours exact', () {
    expect(scheme.primary, BrandColors.primary);
    expect(scheme.tertiary, BrandColors.accent);
    expect(scheme.error, BrandColors.error);
  });

  test('carries the status colours', () {
    expect(theme.extension<StatusColors>(), StatusColors.light);
  });

  test('page background follows the colour scheme', () {
    expect(theme.scaffoldBackgroundColor, scheme.surface);
  });

  // WCAG AA asks for 4.5:1 for body text. If a new brand colour fails here,
  // pick a darker shade for it rather than lowering the bar.
  group('text is readable', () {
    final pairs = {
      'onPrimary on primary': (scheme.onPrimary, scheme.primary),
      'onSurface on surface': (scheme.onSurface, scheme.surface),
      'onSurfaceVariant on surface': (scheme.onSurfaceVariant, scheme.surface),
      'onSurface on surfaceContainerLow': (
        scheme.onSurface,
        scheme.surfaceContainerLow,
      ),
      'primary on surface': (scheme.primary, scheme.surface),
    };
    pairs.forEach((name, pair) {
      test(name, () {
        expect(contrast(pair.$1, pair.$2), greaterThanOrEqualTo(4.5));
      });
    });
  });
}
