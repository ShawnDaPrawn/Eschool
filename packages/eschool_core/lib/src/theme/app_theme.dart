import 'package:eschool_core/src/theme/brand_colors.dart';
import 'package:eschool_core/src/theme/status_colors.dart';
import 'package:flutter/material.dart';

/// The Material 3 theme both apps use.
///
/// The colour scheme is generated from [BrandColors.primary] with the
/// `fidelity` variant, which keeps the brand colour exact instead of toning it
/// down the way the default variant does. Components use Material 3's defaults
/// except for the corner radii below, which keep the apps' existing shapes.
abstract final class AppTheme {
  /// Corner radius of buttons, text fields and cards.
  static const double radius = 10;

  /// Corner radius of the top of bottom sheets.
  static const double bottomSheetRadius = 20;

  static ColorScheme lightColorScheme() {
    // The tertiary roles are the accent's own tonal palette, generated the
    // same way as the primary's. Fidelity puts the exact accent colour in
    // tertiaryContainer and derives a darker tertiary that white text is
    // readable on. The error roles are Material 3's own, for the same reason.
    final accent = ColorScheme.fromSeed(
      seedColor: BrandColors.accent,
      dynamicSchemeVariant: DynamicSchemeVariant.fidelity,
    );
    return ColorScheme.fromSeed(
      seedColor: BrandColors.primary,
      dynamicSchemeVariant: DynamicSchemeVariant.fidelity,
      primary: BrandColors.primary,
      tertiary: accent.primary,
      onTertiary: accent.onPrimary,
      tertiaryContainer: accent.primaryContainer,
      onTertiaryContainer: accent.onPrimaryContainer,
    );
  }

  /// [font] applies the apps' typeface to the Material 3 type scale, for
  /// example `GoogleFonts.poppinsTextTheme`. Tests leave it out.
  static ThemeData light({TextTheme Function(TextTheme textTheme)? font}) {
    final colorScheme = lightColorScheme();
    final base = ThemeData(colorScheme: colorScheme);
    final shape = RoundedRectangleBorder(
      borderRadius: BorderRadius.circular(radius),
    );

    return base.copyWith(
      textTheme: font?.call(base.textTheme) ?? base.textTheme,
      primaryTextTheme:
          font?.call(base.primaryTextTheme) ?? base.primaryTextTheme,
      extensions: [StatusColors.from(colorScheme)],
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(shape: shape),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(shape: shape),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(shape: shape),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(shape: shape),
      ),
      floatingActionButtonTheme: FloatingActionButtonThemeData(
        backgroundColor: colorScheme.primary,
        foregroundColor: colorScheme.onPrimary,
      ),
      inputDecorationTheme: InputDecorationThemeData(
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(radius)),
      ),
      cardTheme: CardThemeData(shape: shape),
      bottomSheetTheme: const BottomSheetThemeData(
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(
            top: Radius.circular(bottomSheetRadius),
          ),
        ),
      ),
      snackBarTheme: const SnackBarThemeData(
        behavior: SnackBarBehavior.floating,
      ),
    );
  }
}
