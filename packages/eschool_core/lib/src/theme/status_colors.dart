import 'dart:math' as math;

import 'package:eschool_core/src/theme/brand_colors.dart';
import 'package:flutter/material.dart';

/// Colours for states Material 3's colour scheme has no role for: present,
/// absent, pending, paid and so on. Read them with `StatusColors.of(context)`.
///
/// Each status colour has its own `on` colour for text and icons drawn on it,
/// picked from the scheme's light and dark text colours for contrast.
@immutable
class StatusColors extends ThemeExtension<StatusColors> {
  const StatusColors({
    required this.success,
    required this.onSuccess,
    required this.warning,
    required this.onWarning,
    required this.danger,
    required this.onDanger,
    required this.info,
    required this.onInfo,
  });

  /// The brand's status colours, with text colours chosen for [scheme].
  factory StatusColors.from(ColorScheme scheme) {
    Color readableOn(Color background) {
      final light = scheme.surfaceContainerLowest;
      final dark = scheme.onSurface;
      return _contrast(light, background) >= _contrast(dark, background)
          ? light
          : dark;
    }

    return StatusColors(
      success: BrandColors.success,
      onSuccess: readableOn(BrandColors.success),
      warning: BrandColors.warning,
      onWarning: readableOn(BrandColors.warning),
      danger: BrandColors.danger,
      onDanger: readableOn(BrandColors.danger),
      info: BrandColors.info,
      onInfo: readableOn(BrandColors.info),
    );
  }

  static StatusColors of(BuildContext context) {
    final theme = Theme.of(context);
    return theme.extension<StatusColors>() ??
        StatusColors.from(theme.colorScheme);
  }

  final Color success;
  final Color onSuccess;
  final Color warning;
  final Color onWarning;
  final Color danger;
  final Color onDanger;
  final Color info;
  final Color onInfo;

  /// The four status colours in a fixed order, for things that cycle through
  /// colours, such as a teacher's list of classes.
  List<Color> get cycle => [info, danger, warning, success];

  @override
  StatusColors copyWith({
    Color? success,
    Color? onSuccess,
    Color? warning,
    Color? onWarning,
    Color? danger,
    Color? onDanger,
    Color? info,
    Color? onInfo,
  }) {
    return StatusColors(
      success: success ?? this.success,
      onSuccess: onSuccess ?? this.onSuccess,
      warning: warning ?? this.warning,
      onWarning: onWarning ?? this.onWarning,
      danger: danger ?? this.danger,
      onDanger: onDanger ?? this.onDanger,
      info: info ?? this.info,
      onInfo: onInfo ?? this.onInfo,
    );
  }

  @override
  StatusColors lerp(StatusColors? other, double t) {
    if (other == null) {
      return this;
    }
    return StatusColors(
      success: Color.lerp(success, other.success, t)!,
      onSuccess: Color.lerp(onSuccess, other.onSuccess, t)!,
      warning: Color.lerp(warning, other.warning, t)!,
      onWarning: Color.lerp(onWarning, other.onWarning, t)!,
      danger: Color.lerp(danger, other.danger, t)!,
      onDanger: Color.lerp(onDanger, other.onDanger, t)!,
      info: Color.lerp(info, other.info, t)!,
      onInfo: Color.lerp(onInfo, other.onInfo, t)!,
    );
  }

  @override
  bool operator ==(Object other) =>
      other is StatusColors &&
      other.success == success &&
      other.onSuccess == onSuccess &&
      other.warning == warning &&
      other.onWarning == onWarning &&
      other.danger == danger &&
      other.onDanger == onDanger &&
      other.info == info &&
      other.onInfo == onInfo;

  @override
  int get hashCode => Object.hash(
    success,
    onSuccess,
    warning,
    onWarning,
    danger,
    onDanger,
    info,
    onInfo,
  );
}

// WCAG contrast ratio of two colours.
double _contrast(Color a, Color b) {
  final la = a.computeLuminance();
  final lb = b.computeLuminance();
  return (math.max(la, lb) + 0.05) / (math.min(la, lb) + 0.05);
}
