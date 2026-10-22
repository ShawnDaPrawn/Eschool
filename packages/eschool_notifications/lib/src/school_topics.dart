import 'package:eschool_notifications/src/notification_platform.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Keeps a device on the FCM topics of the schools its user belongs to.
///
/// The server sends a school-wide notice to `school_{id}_{audience}_{platform}`
/// and, for school 1 only, also to the topic names apps used before Phase 1
/// (`parentsAndroid`, `allIOS` and so on) for the app versions still
/// installed. This app joins the per-school topics of every school
/// `/me/context` names and leaves the old names, so a device gets each
/// notice once and never another school's.
class SchoolTopics {
  SchoolTopics({required this.subscribe, required this.unsubscribe});

  /// The per-school topics this device has joined.
  static const String joinedKey = 'joinedSchoolTopics';

  /// Set once the topic names from before Phase 1 have been left.
  static const String leftLegacyKey = 'leftLegacyTopics';

  final Future<void> Function(String topic) subscribe;
  final Future<void> Function(String topic) unsubscribe;

  /// Joins [schoolIds]' topics for [audience] and leaves the topics of
  /// schools the user no longer belongs to (and, once, the old names). A
  /// topic FCM fails to join is tried again at the next call.
  Future<void> sync(
    NotificationAudience audience,
    Iterable<int> schoolIds,
  ) async {
    final SharedPreferences prefs = await SharedPreferences.getInstance();
    if (!(prefs.getBool(leftLegacyKey) ?? false)) {
      if (await _leaveAll(audience.topics)) {
        await prefs.setBool(leftLegacyKey, true);
      }
    }

    final Set<String> wanted = audience.schoolTopics(schoolIds).toSet();
    final Set<String> joined = (prefs.getStringList(joinedKey) ?? const [])
        .toSet();
    for (final String topic in wanted.difference(joined)) {
      try {
        await subscribe(topic);
        joined.add(topic);
      } catch (_) {
        // Not joined: the next sync tries again.
      }
    }
    for (final String topic in joined.difference(wanted).toList()) {
      try {
        await unsubscribe(topic);
        joined.remove(topic);
      } catch (_) {
        // Still joined: the next sync tries again.
      }
    }
    await prefs.setStringList(joinedKey, joined.toList()..sort());
  }

  /// At sign-out: leaves every topic this device joined, and the old names
  /// of [audience] in case an older version of the app joined them.
  Future<void> signedOut(NotificationAudience audience) async {
    final SharedPreferences prefs = await SharedPreferences.getInstance();
    await _leaveAll([
      ...prefs.getStringList(joinedKey) ?? const <String>[],
      ...audience.topics,
    ]);
    await prefs.remove(joinedKey);
  }

  /// Leaves each of [topics]; answers whether every one was left.
  Future<bool> _leaveAll(Iterable<String> topics) async {
    bool all = true;
    for (final String topic in topics) {
      try {
        await unsubscribe(topic);
      } catch (_) {
        all = false;
      }
    }
    return all;
  }
}
