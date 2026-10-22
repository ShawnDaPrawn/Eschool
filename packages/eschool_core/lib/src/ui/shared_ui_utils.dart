import 'package:eschool_core/src/api/api_exception.dart';
import 'package:eschool_core/src/l10n/app_localization.dart';
import 'package:eschool_core/src/shell/app_shell.dart';
import 'package:eschool_core/src/ui/shared_assets.dart';
import 'package:flutter/material.dart';

/// The sizes and helpers that were the same in both apps' `UiUtils`. Each
/// app's `UiUtils` keeps its own names for them and forwards here, so app code
/// and shared widgets lay out alike.
// ignore: avoid_classes_with_only_static_members
abstract final class SharedUiUtils {
  static const double screenContentTopPadding = 15;
  static const double screenContentHorizontalPadding = 25;
  static const double screenTitleFontSize = 18;
  static const double screenSubTitleFontSize = 14;
  static const double extraScreenContentTopPaddingForScrolling = 0.02;

  static const double appBarSmallerHeightPercentage = 0.15;
  static const double appBarBiggerHeightPercentage = 0.24;

  static const double bottomNavigationBottomMargin = 25;
  static const double appBarContentTopPadding = 25;
  static const double bottomSheetTopRadius = 20;
  static const double bottomSheetHorizontalContentPadding = 20;

  static const double subjectFirstLetterFontSize = 20;

  static const Duration tabBackgroundContainerAnimationDuration = Duration(
    milliseconds: 300,
  );
  static const Curve tabBackgroundContainerAnimationCurve = Curves.easeInOut;

  static const double shimmerLoadingContainerDefaultHeight = 7;
  static const int defaultChatShimmerLoaders = 15;

  static const double textFieldFontSize = 15;

  /// Room to leave under a list so its end clears the bottom bar, whose
  /// height each app sets in its [AppShell].
  static double getScrollViewBottomPadding(BuildContext context) {
    return MediaQuery.sizeOf(context).height *
            AppShell.of(context).bottomNavigationHeightPercentage +
        bottomNavigationBottomMargin * 1.5;
  }

  static double getScrollViewTopPadding({
    required BuildContext context,
    required double appBarHeightPercentage,
    bool keepExtraSpace = true,
  }) {
    return MediaQuery.sizeOf(context).height *
        (appBarHeightPercentage +
            (keepExtraSpace ? extraScreenContentTopPaddingForScrolling : 0));
  }

  static ColorScheme getColorScheme(BuildContext context) {
    return Theme.of(context).colorScheme;
  }

  static Locale getLocaleFromLanguageCode(String languageCode) {
    return AppLocalization.localeFromLanguageCode(languageCode);
  }

  static String getTranslatedLabel(BuildContext context, String labelKey) {
    return (AppLocalization.of(context)!.getTranslatedValues(labelKey) ??
            labelKey)
        .trim();
  }

  /// The translated message for an error code, or the server's own message
  /// when it sent one to show as it is.
  static String getErrorMessageFromErrorCode(
    BuildContext context,
    ApiException exception,
  ) {
    return exception.isApiMessage
        ? exception.errorMessage
        : getTranslatedLabel(
            context,
            AppShell.of(
              context,
            ).errorMessageKeyFromCode(exception.errorMessage),
          );
  }

  static Future<dynamic> showBottomSheet({
    required Widget child,
    required BuildContext context,
    bool? enableDrag,
  }) async {
    final result = await showModalBottomSheet(
      enableDrag: enableDrag ?? false,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.only(
          topLeft: Radius.circular(bottomSheetTopRadius),
          topRight: Radius.circular(bottomSheetTopRadius),
        ),
      ),
      context: context,
      builder: (_) => child,
    );

    return result;
  }

  static String getBackButtonPath(BuildContext context) {
    return Directionality.of(context).name == TextDirection.rtl.name
        ? SharedAssets.rtlBackIcon
        : SharedAssets.backIcon;
  }

  static Widget buildProgressContainer({
    required double width,
    required Color color,
  }) {
    return Container(
      width: width,
      decoration: BoxDecoration(
        color: color,
        borderRadius: BorderRadius.circular(3),
      ),
    );
  }

  static String formatDate(DateTime dateTime) {
    return "${dateTime.day.toString().padLeft(2, '0')}-${dateTime.month.toString().padLeft(2, '0')}-${dateTime.year}";
  }

  static String? getYoutubeThumbnailUrl(String youtubeUrl) {
    try {
      final uri = Uri.parse(youtubeUrl);
      String? videoId;

      // youtu.be/<id>
      if (uri.host.contains('youtu.be')) {
        videoId = uri.pathSegments.isNotEmpty ? uri.pathSegments[0] : null;
      }
      // youtube.com/*
      else if (uri.host.contains('youtube.com')) {
        if (uri.path == '/watch') {
          videoId = uri.queryParameters['v'];
        } else if (uri.pathSegments.isNotEmpty) {
          videoId = uri.pathSegments.last;
        }
      }

      if (videoId == null || videoId.isEmpty) return null;

      // hqdefault is always available
      return 'https://img.youtube.com/vi/$videoId/hqdefault.jpg';
    } catch (_) {
      return null;
    }
  }
}
