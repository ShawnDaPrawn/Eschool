//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'exam_coach_question.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ExamCoachQuestion {
  /// Returns a new [ExamCoachQuestion] instance.
  ExamCoachQuestion({
    required this.number,

    required this.prompt,

    this.memo,

    required this.maxUnits,

    required this.topicIds,
  });

  @JsonKey(name: r'number', required: true, includeIfNull: false)
  final String number;

  @JsonKey(name: r'prompt', required: true, includeIfNull: false)
  final String prompt;

  @JsonKey(name: r'memo', required: false, includeIfNull: false)
  final String? memo;

  // minimum: 1
  // maximum: 100000
  @JsonKey(name: r'max_units', required: true, includeIfNull: false)
  final int maxUnits;

  @JsonKey(name: r'topic_ids', required: true, includeIfNull: false)
  final List<int> topicIds;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ExamCoachQuestion &&
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

  factory ExamCoachQuestion.fromJson(Map<String, dynamic> json) =>
      _$ExamCoachQuestionFromJson(json);

  Map<String, dynamic> toJson() => _$ExamCoachQuestionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
