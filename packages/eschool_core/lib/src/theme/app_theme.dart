import 'package:eschool_core/src/theme/brand_colors.dart';
import 'package:eschool_core/src/theme/status_colors.dart';
import 'package:flutter/material.dart';

/// The Material 3 theme both apps use, in the Navigate Wealth design
/// system's colours ([BrandColors]).
///
/// The primary, accent and error colours each get a full tonal palette from
/// `ColorScheme.fromSeed` with the `fidelity` variant, which keeps the brand
/// colour exact. The greys are the design system's own. Components use
/// Material 3's defaults except for the corner radii below, which keep the
/// apps' existing shapes.
abstract final class AppTheme {
  /// Corner radius of buttons, text fields and cards.
  static const double radius = 10;

  /// Corner radius of the top of bottom sheets.
  static const double bottomSheetRadius = 20;

  static ColorScheme lightColorScheme() {
    // Fidelity puts the source colour in the container role and derives a
    // main role that white text reads on: the accent is exactly
    // tertiaryContainer, and errorContainer is the design system's red
    // deepened just enough for white text (the exact red is
    // StatusColors.danger).
    final accent = _palette(BrandColors.accent);
    final error = _palette(BrandColors.danger);
    // The design system's greys are cool slate rather than tinted by the
    // purple, so the greys it doesn't name come from its muted text colour.
    final neutral = ColorScheme.fromSeed(seedColor: BrandColors.mutedText);
    return ColorScheme.fromSeed(
      seedColor: BrandColors.primary,
      dynamicSchemeVariant: DynamicSchemeVariant.fidelity,
      primary: BrandColors.primary,
      tertiary: accent.primary,
      onTertiary: accent.onPrimary,
      tertiaryContainer: accent.primaryContainer,
      onTertiaryContainer: BrandColors.background,
      error: error.primary,
      onError: error.onPrimary,
      errorContainer: error.primaryContainer,
      onErrorContainer: error.onPrimaryContainer,
      surface: BrandColors.background,
      onSurface: BrandColors.text,
      onSurfaceVariant: BrandColors.mutedText,
      surfaceContainerLowest: BrandColors.background,
      surfaceContainerLow: BrandColors.lightGray,
      surfaceContainer: neutral.surfaceContainer,
      surfaceContainerHigh: neutral.surfaceContainerHigh,
      surfaceContainerHighest: neutral.surfaceContainerHighest,
      surfaceDim: neutral.surfaceDim,
      surfaceBright: BrandColors.background,
      outline: neutral.outline,
      outlineVariant: BrandColors.border,
      inverseSurface: BrandColors.darkNavy,
      onInverseSurface: BrandColors.background,
    );
  }

  static ColorScheme _palette(Color source) => ColorScheme.fromSeed(
    seedColor: source,
    dynamicSchemeVariant: DynamicSchemeVariant.fidelity,
  );

  /// Apply a font to Flutter's type scale without depending on the separate
  /// material_ui TextTheme returned by Google Fonts 9's text-theme helpers.
  static TextTheme applyFont(
    TextTheme theme,
    TextStyle Function({TextStyle? textStyle}) font,
  ) => TextTheme(
    displayLarge: font(textStyle: theme.displayLarge),
    displayMedium: font(textStyle: theme.displayMedium),
    displaySmall: font(textStyle: theme.displaySmall),
    headlineLarge: font(textStyle: theme.headlineLarge),
    headlineMedium: font(textStyle: theme.headlineMedium),
    headlineSmall: font(textStyle: theme.headlineSmall),
    titleLarge: font(textStyle: theme.titleLarge),
    titleMedium: font(textStyle: theme.titleMedium),
    titleSmall: font(textStyle: theme.titleSmall),
    bodyLarge: font(textStyle: theme.bodyLarge),
    bodyMedium: font(textStyle: theme.bodyMedium),
    bodySmall: font(textStyle: theme.bodySmall),
    labelLarge: font(textStyle: theme.labelLarge),
    labelMedium: font(textStyle: theme.labelMedium),
    labelSmall: font(textStyle: theme.labelSmall),
  );

  /// [font] applies the apps' typeface to the Material 3 type scale, for
  /// example `(theme) => applyFont(theme, GoogleFonts.poppins)`. Tests leave it out.
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
