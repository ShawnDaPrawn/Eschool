// Generated union compatibility for dart-dio 7.25.0.
import 'package:eschool_api/src/model/practice_daily_set.dart';
import 'package:eschool_api/src/model/practice_daily_set_v2.dart';
import 'package:eschool_api/src/model/practice_set_entry.dart';

/// The opt-in contract exposes its answer-free questions through [v2].
class IssuePracticeDailySetResponseData {
  const IssuePracticeDailySetResponseData._({this.legacy, this.v2});

  final PracticeDailySet? legacy;
  final PracticeDailySetV2? v2;

  factory IssuePracticeDailySetResponseData.fromJson(
    Map<String, dynamic> json,
  ) {
    final items = json['items'];
    final version2 =
        items is List &&
        items.any((item) => item is Map && item.containsKey('question'));
    return version2
        ? IssuePracticeDailySetResponseData._(
            v2: PracticeDailySetV2.fromJson(json),
          )
        : IssuePracticeDailySetResponseData._(
            legacy: PracticeDailySet.fromJson(json),
          );
  }

  int get id => v2?.id ?? legacy!.id;
  int get schoolId => v2?.schoolId ?? legacy!.schoolId;
  int get readerId => v2?.readerId ?? legacy!.readerId;
  int get learnerId => v2?.learnerId ?? legacy!.learnerId;
  int get groupId => v2?.groupId ?? legacy!.groupId;
  String get practiceOn => v2?.practiceOn ?? legacy!.practiceOn;
  DateTime get serverTime => v2?.serverTime ?? legacy!.serverTime;
  DateTime get offlineUntil => v2?.offlineUntil ?? legacy!.offlineUntil;
  int get estimatedSeconds => v2?.estimatedSeconds ?? legacy!.estimatedSeconds;
  bool get completed => v2?.completed ?? legacy!.completed;
  int get streakDays => v2?.streakDays ?? legacy!.streakDays;

  /// Legacy callers retain their typed items. Contract 2 callers use v2.items;
  /// never manufacture legacy answers from an answer-free response.
  List<PracticeSetEntry> get items =>
      legacy?.items ??
      (throw StateError('Use v2.items for practice contract 2.'));

  Map<String, dynamic> toJson() => v2?.toJson() ?? legacy!.toJson();
}
