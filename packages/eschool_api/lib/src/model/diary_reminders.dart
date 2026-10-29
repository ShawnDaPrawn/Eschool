//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/diary_reminder.dart';
import 'package:json_annotation/json_annotation.dart';

part 'diary_reminders.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DiaryReminders {
  /// Returns a new [DiaryReminders] instance.
  DiaryReminders({
    required this.schoolId,

    required this.readerId,

    required this.reminders,
  });

  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  @JsonKey(name: r'reminders', required: true, includeIfNull: false)
  final List<DiaryReminder> reminders;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DiaryReminders &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.reminders == reminders;

  @override
  int get hashCode =>
      schoolId.hashCode + readerId.hashCode + reminders.hashCode;

  factory DiaryReminders.fromJson(Map<String, dynamic> json) =>
      _$DiaryRemindersFromJson(json);

  Map<String, dynamic> toJson() => _$DiaryRemindersToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
