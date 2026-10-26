//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'exam_coach_study_day.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ExamCoachStudyDay {
  /// Returns a new [ExamCoachStudyDay] instance.
  ExamCoachStudyDay({
    required this.date,

    required this.topicId,

    required this.minutes,

    required this.topicName,
  });

  @JsonKey(name: r'date', required: true, includeIfNull: false)
  final String date;

  // minimum: 1
  @JsonKey(name: r'topic_id', required: true, includeIfNull: false)
  final int topicId;

  // minimum: 10
  // maximum: 60
  @JsonKey(name: r'minutes', required: true, includeIfNull: false)
  final int minutes;

  @JsonKey(name: r'topic_name', required: true, includeIfNull: false)
  final String topicName;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ExamCoachStudyDay &&
          other.date == date &&
          other.topicId == topicId &&
          other.minutes == minutes &&
          other.topicName == topicName;

  @override
  int get hashCode =>
      date.hashCode + topicId.hashCode + minutes.hashCode + topicName.hashCode;

  factory ExamCoachStudyDay.fromJson(Map<String, dynamic> json) =>
      _$ExamCoachStudyDayFromJson(json);

  Map<String, dynamic> toJson() => _$ExamCoachStudyDayToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
