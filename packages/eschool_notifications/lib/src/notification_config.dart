import 'package:firebase_messaging/firebase_messaging.dart';

/// The user a chat notification is from. Each app's `ChatUser` implements
/// it, so the notification code can show the name and photo and keep the
/// user, without knowing the rest of the app's model.
abstract interface class ChatSender {
  int get userId;
  String get userName;
  String get profileUrl;
  Map<String, dynamic> toMap();
}

/// What differs between the apps' notifications. Each app builds one as a
/// top-level value, so its background handler (which runs in an isolate of
/// its own) can use it too.
class NotificationConfig {
  const NotificationConfig({
    required this.basicChannelDescription,
    required this.typesNotCounted,
    required this.includeDataInPayload,
    required this.onTap,
    required this.backgroundHandler,
    required this.chatSenderFromApi,
    required this.chatSenderFromMap,
  });

  /// The description of the general Android channel (`basic_channel`).
  final String basicChannelDescription;

  /// `data['type']` values that do not raise the unread badge.
  final List<String> typesNotCounted;

  /// Whether a local notification carries the whole push payload (the
  /// learner app needs `child_id` for fee reminders) or only its type.
  final bool includeDataInPayload;

  /// Opens the screen for a tapped notification, by `data['type']`.
  final void Function(String type, Map<String, dynamic> data) onTap;

  /// The app's top-level `@pragma('vm:entry-point')` handler, which calls
  /// `NotificationUtility.handleBackgroundMessage` with this config.
  final Future<void> Function(RemoteMessage message) backgroundHandler;

  /// The app's `ChatUser.fromJsonAPI` and `ChatUser.fromMap`.
  final ChatSender Function(Map<dynamic, dynamic> json) chatSenderFromApi;
  final ChatSender Function(Map<String, dynamic> map) chatSenderFromMap;
}
