import 'package:flutter/widgets.dart';

/// The three widths both apps lay out for.
enum ScreenSize {
  /// Narrower than 600: every phone in portrait. The apps look exactly as
  /// they were designed here.
  phone,

  /// 600 to 1024: tablets and small browser windows.
  tablet,

  /// Wider than 1024: laptops and desktop browsers.
  desktop,
}

/// Where the layout changes, by the width the app has (not the device's).
// ignore: avoid_classes_with_only_static_members
abstract final class Breakpoints {
  /// From this width up a screen is a [ScreenSize.tablet].
  static const double tablet = 600;

  /// Above this width a screen is a [ScreenSize.desktop].
  static const double desktop = 1024;

  /// The widest a screen's content is drawn. The screens were designed for
  /// phones, so on tablets and desktops they keep a phone-like column
  /// instead of stretching across the window.
  static const double contentMaxWidth = 640;

  static ScreenSize forWidth(double width) {
    if (width < tablet) {
      return ScreenSize.phone;
    }
    if (width <= desktop) {
      return ScreenSize.tablet;
    }
    return ScreenSize.desktop;
  }

  /// The [ScreenSize] for the width [context] has.
  static ScreenSize of(BuildContext context) =>
      forWidth(MediaQuery.sizeOf(context).width);

  static bool isPhone(BuildContext context) => of(context) == ScreenSize.phone;
}
