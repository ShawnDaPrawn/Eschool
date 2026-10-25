//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/practice_set_entry.dart';
import 'package:json_annotation/json_annotation.dart';

part 'practice_daily_set.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeDailySet {
  /// Returns a new [PracticeDailySet] instance.
  PracticeDailySet({
    required this.id,

    required this.schoolId,

    required this.readerId,

    required this.learnerId,

    required this.groupId,

    required this.practiceOn,

    required this.serverTime,

    required this.offlineUntil,

    required this.estimatedSeconds,

    required this.completed,

    required this.items,

    required this.streakDays,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  // minimum: 1
  @JsonKey(name: r'group_id', required: true, includeIfNull: false)
  final int groupId;

  @JsonKey(name: r'practice_on', required: true, includeIfNull: false)
  final String practiceOn;

  @JsonKey(name: r'server_time', required: true, includeIfNull: false)
  final DateTime serverTime;

  @JsonKey(name: r'offline_until', required: true, includeIfNull: false)
  final DateTime offlineUntil;

  // minimum: 60
  // maximum: 600
  @JsonKey(name: r'estimated_seconds', required: true, includeIfNull: false)
  final int estimatedSeconds;

  @JsonKey(name: r'completed', required: true, includeIfNull: false)
  final bool completed;

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<PracticeSetEntry> items;

  // minimum: 0
  // maximum: 365
  @JsonKey(name: r'streak_days', required: true, includeIfNull: false)
  final int streakDays;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeDailySet &&
          other.id == id &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.learnerId == learnerId &&
          other.groupId == groupId &&
          other.practiceOn == practiceOn &&
          other.serverTime == serverTime &&
          other.offlineUntil == offlineUntil &&
          other.estimatedSeconds == estimatedSeconds &&
          other.completed == completed &&
          other.items == items &&
          other.streakDays == streakDays;

  @override
  int get hashCode =>
      id.hashCode +
      schoolId.hashCode +
      readerId.hashCode +
      learnerId.hashCode +
      groupId.hashCode +
      practiceOn.hashCode +
      serverTime.hashCode +
      offlineUntil.hashCode +
      estimatedSeconds.hashCode +
      completed.hashCode +
      items.hashCode +
      streakDays.hashCode;

  factory PracticeDailySet.fromJson(Map<String, dynamic> json) =>
      _$PracticeDailySetFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeDailySetToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
