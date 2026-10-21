import 'package:flutter/material.dart';

/// The colour tokens of the Navigate Wealth design system, which NavigateEd
/// shares. [AppTheme] builds both apps' Material 3 colour scheme from these,
/// so changing the brand means changing this file only.
///
/// Read colours in widgets through `Theme.of(context).colorScheme` and
/// [StatusColors], not from here. Use these constants directly only where
/// there is no [BuildContext], such as the accent colour of an Android
/// notification.
abstract final class BrandColors {
  /// Primary Purple: buttons, links, active states and icons. Seeds the
  /// colour scheme and is used unchanged as its `primary`.
  static const Color primary = Color(0xff6d28d9);

  /// Dark Navy: hero areas and dark sections. The apps' screen headers are
  /// the scheme's `inverseSurface`, which is this colour.
  static const Color darkNavy = Color(0xff313653);

  /// The second brand colour. Seeds the scheme's tertiary roles and is used
  /// unchanged as its `tertiaryContainer`.
  static const Color accent = darkNavy;

  /// White: page backgrounds, cards and dialogs (`surface`).
  static const Color background = Color(0xffffffff);

  /// Light Gray: input backgrounds, skeleton loaders and card fills
  /// (`surfaceContainerLow`).
  static const Color lightGray = Color(0xfff8f9fa);

  /// Black: headings and body text (`onSurface`).
  static const Color text = Color(0xff000000);

  /// Muted Gray: secondary text, captions and placeholders
  /// (`onSurfaceVariant`). Also seeds the scheme's other greys.
  static const Color mutedText = Color(0xff64748b);

  /// Border Gray: card borders and dividers (`outlineVariant`).
  static const Color border = Color(0x1a000000);

  /// Status colours, see [StatusColors]. Text on them is picked for contrast,
  /// so they can be as light as the brand needs. The design system has no
  /// info colour; this is the blue from the same Tailwind palette as the
  /// other three.
  static const Color success = Color(0xff22c55e);
  static const Color warning = Color(0xffeab308);
  static const Color danger = Color(0xffef4444);
  static const Color info = Color(0xff3b82f6);
}
