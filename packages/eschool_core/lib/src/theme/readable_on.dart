import 'dart:math' as math;

import 'package:flutter/material.dart';

extension ReadableOn on ColorScheme {
  /// The scheme's light or dark text colour, whichever reads better on
  /// [background]. For text on a colour that isn't a scheme role: one that
  /// comes from the server, or a background that changes at runtime.
  Color readableOn(Color background) {
    final light = surfaceContainerLowest;
    final dark = onSurface;
    return contrastRatio(light, background) >= contrastRatio(dark, background)
        ? light
        : dark;
  }
}

/// The WCAG contrast ratio of two colours, from 1 to 21.
double contrastRatio(Color a, Color b) {
  final la = a.computeLuminance();
  final lb = b.computeLuminance();
  return (math.max(la, lb) + 0.05) / (math.min(la, lb) + 0.05);
}
