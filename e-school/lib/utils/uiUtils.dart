import 'dart:math';

import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool/app/routes.dart';
import 'package:eschool/cubits/appConfigurationCubit.dart';
import 'package:eschool/cubits/appLocalizationCubit.dart';
import 'package:eschool/cubits/downloadFileCubit.dart';
import 'package:eschool/data/repositories/subjectRepository.dart';
import 'package:eschool/ui/widgets/downloadFileBottomsheetContainer.dart';
import 'package:eschool/ui/widgets/errorMessageOverlayContainer.dart';
import 'package:eschool/utils/constants.dart';
import 'package:eschool/utils/errorMessageKeysAndCodes.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:intl/intl.dart' as intl;
import 'package:url_launcher/url_launcher.dart';

import 'package:timeago/timeago.dart' as timeago;

// ignore: avoid_classes_with_only_static_members
class UiUtils {
  //This extra padding will add to MediaQuery.of(context).padding.top in order to give same top padding in every screen

  static const double screenContentTopPadding =
      SharedUiUtils.screenContentTopPadding;
  static const double screenContentHorizontalPadding =
      SharedUiUtils.screenContentHorizontalPadding;
  static const double screenTitleFontSize = SharedUiUtils.screenTitleFontSize;
  static double screenContentHorizontalPaddingInPercentage = 0.075;

  static const double screenSubTitleFontSize =
      SharedUiUtils.screenSubTitleFontSize;
  static const double extraScreenContentTopPaddingForScrolling =
      SharedUiUtils.extraScreenContentTopPaddingForScrolling;

  static double homeAndChatMessagePageAppbarHeightPercentage = 0.16;
  static const double appBarSmallerHeightPercentage =
      SharedUiUtils.appBarSmallerHeightPercentage;
  static double appBarMediumHeightPercentage = 0.19;
  static const double appBarBiggerHeightPercentage =
      SharedUiUtils.appBarBiggerHeightPercentage;

  static double bottomNavigationHeightPercentage = 0.08;
  static const double bottomNavigationBottomMargin =
      SharedUiUtils.bottomNavigationBottomMargin;
  static const double appBarContentTopPadding =
      SharedUiUtils.appBarContentTopPadding;
  static const double bottomSheetTopRadius = SharedUiUtils.bottomSheetTopRadius;

  static const double bottomSheetHorizontalContentPadding =
      SharedUiUtils.bottomSheetHorizontalContentPadding;

  static const double subjectFirstLetterFontSize =
      SharedUiUtils.subjectFirstLetterFontSize;

  static double defaultProfilePictureHeightAndWidthPercentage = 0.175;

  static double questionContainerHeightPercentage = 0.725;

  static const Duration tabBackgroundContainerAnimationDuration =
      SharedUiUtils.tabBackgroundContainerAnimationDuration;

  static Duration showCaseDisplayDelayInDuration = const Duration(
    milliseconds: 350,
  );
  static const Curve tabBackgroundContainerAnimationCurve =
      SharedUiUtils.tabBackgroundContainerAnimationCurve;

  static const double shimmerLoadingContainerDefaultHeight =
      SharedUiUtils.shimmerLoadingContainerDefaultHeight;

  static int defaultShimmerLoadingContentCount = 6;
  static const int defaultChatShimmerLoaders =
      SharedUiUtils.defaultChatShimmerLoaders;

  static const double textFieldFontSize = SharedUiUtils.textFieldFontSize;

  //key for global navigation
  static GlobalKey<NavigatorState> rootNavigatorKey =
      GlobalKey<NavigatorState>();

  static final List<String> weekDays = [
    'Mon',
    'Tue',
    'Wed',
    'Thu',
    'Fri',
    'Sat',
    'Sun',
  ];

  //to give bottom scroll padding in screen where
  //bottom navigation bar is displayed
  static double getScrollViewBottomPadding(BuildContext context) {
    return MediaQuery.sizeOf(context).height *
            (UiUtils.bottomNavigationHeightPercentage) +
        UiUtils.bottomNavigationBottomMargin * 1.5;
  }

  //to give top scroll padding to screen content
  static double getScrollViewTopPadding({
    required BuildContext context,
    required double appBarHeightPercentage,
    bool keepExtraSpace = true,
  }) => SharedUiUtils.getScrollViewTopPadding(
    context: context,
    appBarHeightPercentage: appBarHeightPercentage,
    keepExtraSpace: keepExtraSpace,
  );

  static ColorScheme getColorScheme(BuildContext context) =>
      SharedUiUtils.getColorScheme(context);

  static Locale getLocaleFromLanguageCode(String languageCode) =>
      SharedUiUtils.getLocaleFromLanguageCode(languageCode);

  static String getTranslatedLabel(BuildContext context, String labelKey) =>
      SharedUiUtils.getTranslatedLabel(context, labelKey);

  static Future<dynamic> showBottomSheet({
    required Widget child,
    required BuildContext context,
    bool? enableDrag,
  }) => SharedUiUtils.showBottomSheet(
    child: child,
    context: context,
    enableDrag: enableDrag,
  );

  static bool isTodayInSessionYear(DateTime firstDate, DateTime lastDate) {
    final currentDate = DateTime.now();

    return (currentDate.isAfter(firstDate) && currentDate.isBefore(lastDate)) ||
        isSameDay(firstDate) ||
        isSameDay(lastDate);
  }

  static bool isSameDay(DateTime dateTime) {
    final currentDate = DateTime.now();
    return (currentDate.day == dateTime.day) &&
        (currentDate.month == dateTime.month) &&
        (currentDate.year == dateTime.year);
  }

  static String getMonthName(int monthNumber) {
    final List<String> months = [
      'January',
      'February',
      'March',
      'April',
      'May',
      'June',
      'July',
      'August',
      'September',
      'October',
      'November',
      'December',
    ];
    return months[monthNumber - 1];
  }

  static List<String> buildMonthYearsBetweenTwoDates(
    DateTime startDate,
    DateTime endDate,
  ) {
    final List<String> dateTimes = [];
    DateTime current = startDate;
    while (current.difference(endDate).isNegative) {
      current = current.add(const Duration(days: 24));
      dateTimes.add('${getMonthName(current.month)}, ${current.year}');
    }
    return dateTimes.toSet().toList();
  }

  static String formatTime(String time) {
    final hourMinuteSecond = time.split(':');
    final hour = int.parse(hourMinuteSecond.first) < 13
        ? int.parse(hourMinuteSecond.first)
        : int.parse(hourMinuteSecond.first) - 12;
    final amOrPm = int.parse(hourMinuteSecond.first) > 12 ? 'PM' : 'AM';
    return "${hour.toString().padLeft(2, '0')}:${hourMinuteSecond[1]} $amOrPm";
  }

  static String formatTimeWithDateTime(DateTime dateTime, {bool is24 = true}) {
    if (is24) {
      return intl.DateFormat('kk:mm').format(dateTime);
    } else {
      return intl.DateFormat('hh:mm a').format(dateTime);
    }
  }

  static String formatAssignmentDueDateDayOnly(
    DateTime dateTime,
    BuildContext context,
  ) {
    final value = dateTime.isToday()
        ? UiUtils.getTranslatedLabel(context, todayKey)
        : dateTime.isYesterday()
        ? UiUtils.getTranslatedLabel(context, yesterdayKey)
        : dateTime.isCurrentYear()
        ? intl.DateFormat('dd MMMM').format(dateTime)
        : intl.DateFormat('dd MMM yyyy').format(dateTime);
    return '${UiUtils.getTranslatedLabel(context, dueKey)} ${UiUtils.getTranslatedLabel(context, onKey)} $value';
  }

  static String formatAssignmentDueDate(
    DateTime dateTime,
    BuildContext context,
  ) {
    final monthName = UiUtils.getMonthName(dateTime.month);
    final hour = dateTime.hour < 13 ? dateTime.hour : dateTime.hour - 12;
    final amOrPm = dateTime.hour > 12 ? 'PM' : 'AM';
    return "${UiUtils.getTranslatedLabel(context, dueKey)}, ${dateTime.day} $monthName ${dateTime.year}, ${hour.toString().padLeft(2, '0')}:${dateTime.minute.toString().padLeft(2, '0')} $amOrPm";
  }

  /// True in a web build, where attaching files and paying are not built
  /// yet: it tells the user to do it in the app on their phone instead.
  static bool showMobileOnlyNotice(BuildContext context) {
    if (!kIsWeb) {
      return false;
    }
    showCustomSnackBar(
      context: context,
      errorMessage: getTranslatedLabel(context, availableInMobileAppKey),
      backgroundColor: Theme.of(context).colorScheme.primary,
    );
    return true;
  }

  static Future<void> showCustomSnackBar({
    required BuildContext context,
    required String errorMessage,
    required Color backgroundColor,
    Duration delayDuration = errorMessageDisplayDuration,
  }) async {
    final OverlayState overlayState = Overlay.of(context);
    final OverlayEntry overlayEntry = OverlayEntry(
      builder: (context) => ErrorMessageOverlayContainer(
        backgroundColor: backgroundColor,
        errorMessage: errorMessage,
      ),
    );

    overlayState.insert(overlayEntry);
    await Future.delayed(delayDuration);
    overlayEntry.remove();
  }

  static Color getColorFromHexValue(String hexValue) {
    final int color = int.parse(hexValue.replaceAll('#', '0xff'));
    return Color(color);
  }

  static String getErrorMessageFromErrorCode(
    BuildContext context,
    ApiException exception,
  ) {
    return exception.isApiMessage
        ? exception.errorMessage
        : UiUtils.getTranslatedLabel(
            context,

            ErrorMessageKeysAndCode.getErrorMessageKeyFromCode(
              exception.errorMessage,
            ),
          );
  }

  //0 = Pending/In Review , 1 = Accepted , 2 = Rejected
  static String getAssignmentSubmissionStatusKey(int status) {
    if (status == 0) {
      return inReviewKey;
    }
    if (status == 1) {
      return acceptedKey;
    }
    if (status == 2) {
      return rejectedKey;
    }
    if (status == 3) {
      return resubmittedKey;
    }
    return '';
  }

  static String getBackButtonPath(BuildContext context) =>
      SharedUiUtils.getBackButtonPath(context);

  /// Whether [url] is a web (http/https) link. Links that come from other
  /// users or from the server are only opened when they are, because other
  /// schemes (intent:, file:, custom app schemes, ...) could start other apps
  /// or open local files.
  static bool isWebUrl(String url) {
    final Uri? uri = Uri.tryParse(url.trim());
    return uri != null &&
        (uri.isScheme('https') || uri.isScheme('http')) &&
        uri.host.isNotEmpty;
  }

  /// Opens a link that came from other users or from the server (chat
  /// messages, assignment answers, timetable links, videos), but only if it
  /// is a web link. Returns whether the link was opened.
  static Future<bool> launchWebUrl(
    String url, {
    LaunchMode mode = LaunchMode.externalApplication,
  }) async {
    if (!isWebUrl(url)) {
      return false;
    }
    try {
      return await launchUrl(Uri.parse(url.trim()), mode: mode);
    } catch (_) {
      return false;
    }
  }

  static void viewOrDownloadStudyMaterial({
    required BuildContext context,
    required bool storeInExternalStorage,
    required StudyMaterial studyMaterial,
  }) {
    try {
      if (studyMaterial.fileExtension.toLowerCase() == 'pdf') {
        Navigator.pushNamed(
          context,
          Routes.pdfFileView,
          arguments: PdfFileViewArguments(studyMaterial: studyMaterial),
        );
      } else if (studyMaterial.fileExtension.isImage()) {
        Navigator.pushNamed(
          context,
          Routes.imageFileView,
          arguments: ImageFileViewArguments(studyMaterial: studyMaterial),
        );
      } else {
        if (studyMaterial.studyMaterialType ==
                StudyMaterialType.uploadedVideoUrl ||
            studyMaterial.studyMaterialType == StudyMaterialType.youtubeVideo) {
          launchWebUrl(studyMaterial.fileUrl, mode: LaunchMode.platformDefault);
        } else {
          UiUtils.openDownloadBottomsheet(
            context: context,
            studyMaterial: studyMaterial,
          );
        }
      }
    } catch (e) {
      if (context.mounted) {
        UiUtils.showCustomSnackBar(
          context: context,
          errorMessage: UiUtils.getTranslatedLabel(
            context,
            unableToOpenFileKey,
          ),
          backgroundColor: Theme.of(context).colorScheme.error,
        );
      }
    }
  }

  static void openDownloadBottomsheet({
    required BuildContext context,
    required StudyMaterial studyMaterial,
  }) {
    showBottomSheet(
      child: BlocProvider<DownloadFileCubit>(
        create: (context) => DownloadFileCubit(SubjectRepository()),
        child: DownloadFileBottomsheetContainer(studyMaterial: studyMaterial),
      ),
      context: context,
    ).then((result) async {
      if (result != null) {
        if (result['error']) {
          showCustomSnackBar(
            context: context,
            errorMessage: getErrorMessageFromErrorCode(
              context,
              ApiException(result['message'].toString()),
            ),
            backgroundColor: Theme.of(context).colorScheme.error,
          );
        } else {
          try {
            final bool opened = await DeviceFiles.open(
              result['filePath'].toString(),
            );
            if (!opened) {
              showCustomSnackBar(
                context: context,
                errorMessage: getTranslatedLabel(context, unableToOpenKey),
                backgroundColor: Theme.of(context).colorScheme.error,
              );
            }
          } catch (e) {
            showCustomSnackBar(
              context: context,
              errorMessage: getTranslatedLabel(context, unableToOpenKey),
              backgroundColor: Theme.of(context).colorScheme.error,
            );
          }
        }
      }
    });
  }

  static Widget buildProgressContainer({
    required double width,
    required Color color,
  }) => SharedUiUtils.buildProgressContainer(width: width, color: color);

  static String formatDateAndTime(DateTime dateTime) {
    return intl.DateFormat('dd-MM-yyyy  kk:mm').format(dateTime);
  }

  static String formatDate(DateTime dateTime) =>
      SharedUiUtils.formatDate(dateTime);

  /*  //Date format is DD-MM-YYYY
  static String formatStringDate(String date) {
    final DateTime dateTime = DateTime.parse(date);
    return "${dateTime.day.toString().padLeft(2, '0')}-${dateTime.month.toString().padLeft(2, '0')}-${dateTime.year}";
  } */

  static String dateConverter(
    DateTime myEndDate,
    BuildContext context,
    bool fromResult,
  ) {
    String date;
    //format date & time
    final onlyDate = myEndDate.toString().split(' ');
    final DateTime dt = intl.DateFormat(
      'yyyy-MM-dd',
      'en_US',
    ).parse(onlyDate[0]);
    final formattedDate = intl.DateFormat('dd MMM, yyyy');
    final onlyTime = onlyDate[1];
    final formattedTime = UiUtils.formatTime(onlyTime);
    //check for today or tomorrow or specific date
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    final tomorrow = DateTime(now.year, now.month, now.day + 1);
    final checkEndDate = DateTime(
      myEndDate.year,
      myEndDate.month,
      myEndDate.day,
    );

    if (checkEndDate == today) {
      date = fromResult
          ? '${UiUtils.getTranslatedLabel(context, submittedKey)} : $todayKey'
          : '$todayKey, $formattedTime';
    } else if (checkEndDate == tomorrow) {
      date = fromResult
          ? '${UiUtils.getTranslatedLabel(context, submittedKey)} : $tomorrowKey'
          : '$tomorrowKey, $formattedTime';
    } else {
      date = fromResult
          ? '${UiUtils.getTranslatedLabel(context, submittedKey)} : ${formattedDate.format(dt)}'
          : '${formattedDate.format(dt)} $formattedTime';
    }
    return date;
  }

  //It will return - if given value is empty
  static String formatEmptyValue(String value) {
    return value.isEmpty ? '-' : value;
  }

  /// Whether the server asks this install to update; the rules are in
  /// `ForceUpdate.isRequired` (eschool_session).
  static Future<bool> forceUpdate(String updatedVersion) =>
      ForceUpdate.check(updatedVersion);

  static void showFeatureDisableInDemoVersion(BuildContext context) {
    showCustomSnackBar(
      context: context,
      errorMessage: UiUtils.getTranslatedLabel(
        context,
        featureDisableInDemoVersionKey,
      ),
      backgroundColor: Theme.of(context).colorScheme.error,
    );
  }

  static bool isDemoVersionEnable({required BuildContext context}) {
    return context.read<AppConfigurationCubit>().getIsDemoModeOn();
  }

  //0 = Pending , 1 = Paid, 2 = Partially Paid set according to API response.
  static String getStudentFeesStatusKey(int status) {
    if (status == 0) {
      return pendingKey;
    }
    if (status == 1) {
      return paidKey;
    }
    if (status == 2) {
      return partiallyPaidKey;
    }
    return '';
  }

  static String formatAmount({
    required String strVal,
    required BuildContext context,
  }) {
    return '${context.read<AppConfigurationCubit>().getPaymentOptions().currencySymbol!} $strVal';
  }

  static String getFileSizeString({required int bytes, int decimals = 0}) {
    const suffixes = ['b', 'kb', 'mb', 'gb', 'tb'];
    if (bytes == 0) return '0${suffixes[0]}';
    final i = (log(bytes) / log(1024)).floor();
    return ((bytes / pow(1024, i)).toStringAsFixed(decimals)) + suffixes[i];
  }

  static String getLeavesStatusKey(int status) {
    //0 = Pending , 1 = Accepted , 2 = Rejected
    if (status == 0) {
      return pendingKey;
    }
    if (status == 1) {
      return acceptedKey;
    }
    if (status == 2) {
      return rejectedKey;
    }
    return allKey;
  }

  static String getTimeAgo(BuildContext context, {required DateTime date}) {
    return timeago.format(
      date,
      locale: context.read<AppLocalizationCubit>().state.language.languageCode,
    );
  }

  static String? getYoutubeThumbnailUrl(String youtubeUrl) =>
      SharedUiUtils.getYoutubeThumbnailUrl(youtubeUrl);
}
