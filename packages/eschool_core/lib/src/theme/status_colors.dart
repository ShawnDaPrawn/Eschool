import 'package:eschool_core/src/theme/brand_colors.dart';
import 'package:flutter/material.dart';

/// Colours for states Material 3's colour scheme has no role for: present,
/// absent, pending, paid and so on. Read them with `StatusColors.of(context)`.
@immutable
class StatusColors extends ThemeExtension<StatusColors> {
  const StatusColors({
    required this.success,
    required this.warning,
    required this.danger,
    required this.info,
    required this.onStatus,
  });

  static const StatusColors light = StatusColors(
    success: BrandColors.success,
    warning: BrandColors.warning,
    danger: BrandColors.danger,
    info: BrandColors.info,
    onStatus: Color(0xffffffff),
  );

  static StatusColors of(BuildContext context) =>
      Theme.of(context).extension<StatusColors>() ?? light;

  final Color success;
  final Color warning;
  final Color danger;
  final Color info;

  /// Text and icons drawn on top of any of the four status colours.
  final Color onStatus;

  /// The four status colours in a fixed order, for things that cycle through
  /// colours, such as a teacher's list of classes.
  List<Color> get cycle => [info, danger, warning, success];

  @override
  StatusColors copyWith({
    Color? success,
    Color? warning,
    Color? danger,
    Color? info,
    Color? onStatus,
  }) {
    return StatusColors(
      success: success ?? this.success,
      warning: warning ?? this.warning,
      danger: danger ?? this.danger,
      info: info ?? this.info,
      onStatus: onStatus ?? this.onStatus,
    );
  }

  @override
  StatusColors lerp(StatusColors? other, double t) {
    if (other == null) {
      return this;
    }
    return StatusColors(
      success: Color.lerp(success, other.success, t)!,
      warning: Color.lerp(warning, other.warning, t)!,
      danger: Color.lerp(danger, other.danger, t)!,
      info: Color.lerp(info, other.info, t)!,
      onStatus: Color.lerp(onStatus, other.onStatus, t)!,
    );
  }
}
