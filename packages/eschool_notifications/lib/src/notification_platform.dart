import 'package:flutter/foundation.dart';

/// Which platform the notification code runs on, without `dart:io` (which a
/// web build cannot use).
// ignore: avoid_classes_with_only_static_members
abstract final class NotificationPlatform {
  /// Push notifications through FCM and the local notifications that show
  /// them: Android and iOS. The web portals have none yet (see the wiki's
  /// Notifications page); they list notifications from the API instead.
  static bool get isSupported => !kIsWeb && (isAndroid || isIOS);

  static bool get isAndroid =>
      !kIsWeb && defaultTargetPlatform == TargetPlatform.android;

  static bool get isIOS =>
      !kIsWeb && defaultTargetPlatform == TargetPlatform.iOS;
}

/// Who an FCM topic is for. The server sends school-wide notices to these
/// topics (`PushTopics` in the admin panel).
enum NotificationAudience {
  students,
  parents,
  teachers;

  /// The per-school topics a device of this audience joins: for each of
  /// [schoolIds], its own and the one for everybody at that school, for the
  /// device's platform (for example `school_2_parents_android` and
  /// `school_2_all_android`). None off Android and iOS.
  List<String> schoolTopics(Iterable<int> schoolIds) {
    final String? platform = NotificationPlatform.isAndroid
        ? 'android'
        : NotificationPlatform.isIOS
        ? 'ios'
        : null;
    if (platform == null) {
      return const [];
    }
    return [
      for (final int id in schoolIds.toSet())
        if (id > 0) ...[
          'school_${id}_${name}_$platform',
          'school_${id}_all_$platform',
        ],
    ];
  }

  /// The topic names apps joined before Phase 1 (for example
  /// `parentsAndroid` and `allAndroid`): this version leaves them (see
  /// `SchoolTopics`). None off Android and iOS.
  List<String> get topics {
    final String? platform = NotificationPlatform.isAndroid
        ? 'Android'
        : NotificationPlatform.isIOS
        ? 'IOS'
        : null;
    if (platform == null) {
      return const [];
    }
    return ['$name$platform', 'all$platform'];
  }
}
