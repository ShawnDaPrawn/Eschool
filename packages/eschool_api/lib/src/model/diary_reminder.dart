//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'diary_reminder.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DiaryReminder {
  /// Returns a new [DiaryReminder] instance.
  DiaryReminder({required this.key, required this.at, required this.text});

  /// `entry:<id>` or `<source>:<id>` for a school item; stable, so the phone can replace an earlier reminder.
  @JsonKey(name: r'key', required: true, includeIfNull: false)
  final String key;

  /// When to remind, in UTC.
  @JsonKey(name: r'at', required: true, includeIfNull: false)
  final DateTime at;

  @JsonKey(name: r'text', required: true, includeIfNull: false)
  final String text;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DiaryReminder &&
          other.key == key &&
          other.at == at &&
          other.text == text;

  @override
  int get hashCode => key.hashCode + at.hashCode + text.hashCode;

  factory DiaryReminder.fromJson(Map<String, dynamic> json) =>
      _$DiaryReminderFromJson(json);

  Map<String, dynamic> toJson() => _$DiaryReminderToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
