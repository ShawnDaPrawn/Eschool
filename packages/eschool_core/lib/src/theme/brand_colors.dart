import 'package:flutter/material.dart';

/// The brand colours of both apps. Every other colour the apps draw is
/// derived from these by [AppTheme], so changing the brand means changing
/// this file only.
///
/// Read colours in widgets through `Theme.of(context).colorScheme` and
/// [StatusColors], not from here. Use these constants directly only where
/// there is no [BuildContext], such as the accent colour of an Android
/// notification.
abstract final class BrandColors {
  /// Seeds the Material 3 colour scheme and is used unchanged as its
  /// `primary`.
  static const Color primary = Color(0xff22577A);

  /// The brand's second colour. Seeds the scheme's tertiary roles and is used
  /// unchanged as its `tertiaryContainer`.
  static const Color accent = Color(0xff3bcc97);

  /// Status colours, see [StatusColors]. Text on them is picked for contrast,
  /// so they can be as light as the brand needs.
  static const Color success = Color(0xff57cc99);
  static const Color warning = Color(0xffFFA74F);
  static const Color danger = Color(0xffff6769);
  static const Color info = Color(0xff4FA2FF);
}
