//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'exam_coach_question_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ExamCoachQuestionWrite {
  /// Returns a new [ExamCoachQuestionWrite] instance.
  ExamCoachQuestionWrite({
    required this.number,

    required this.prompt,

    required this.memo,

    required this.maxUnits,

    required this.topicIds,
  });

  @JsonKey(name: r'number', required: true, includeIfNull: false)
  final String number;

  @JsonKey(name: r'prompt', required: true, includeIfNull: false)
  final String prompt;

  @JsonKey(name: r'memo', required: true, includeIfNull: false)
  final String memo;

  // minimum: 1
  // maximum: 100000
  @JsonKey(name: r'max_units', required: true, includeIfNull: false)
  final int maxUnits;

  @JsonKey(name: r'topic_ids', required: true, includeIfNull: false)
  final List<int> topicIds;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ExamCoachQuestionWrite &&
          other.number == number &&
          other.prompt == prompt &&
          other.memo == memo &&
          other.maxUnits == maxUnits &&
          other.topicIds == topicIds;

  @override
  int get hashCode =>
      number.hashCode +
      prompt.hashCode +
      memo.hashCode +
      maxUnits.hashCode +
      topicIds.hashCode;

  factory ExamCoachQuestionWrite.fromJson(Map<String, dynamic> json) =>
      _$ExamCoachQuestionWriteFromJson(json);

  Map<String, dynamic> toJson() => _$ExamCoachQuestionWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
