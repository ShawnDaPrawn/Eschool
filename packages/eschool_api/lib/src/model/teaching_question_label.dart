//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'teaching_question_label.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TeachingQuestionLabel {
  /// Returns a new [TeachingQuestionLabel] instance.
  TeachingQuestionLabel({
    required this.questionId,

    required this.number,

    required this.maxMark,
  });

  // minimum: 1
  @JsonKey(name: r'question_id', required: true, includeIfNull: false)
  final int questionId;

  @JsonKey(name: r'number', required: true, includeIfNull: false)
  final String number;

  @JsonKey(name: r'max_mark', required: true, includeIfNull: false)
  final String maxMark;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TeachingQuestionLabel &&
          other.questionId == questionId &&
          other.number == number &&
          other.maxMark == maxMark;

  @override
  int get hashCode => questionId.hashCode + number.hashCode + maxMark.hashCode;

  factory TeachingQuestionLabel.fromJson(Map<String, dynamic> json) =>
      _$TeachingQuestionLabelFromJson(json);

  Map<String, dynamic> toJson() => _$TeachingQuestionLabelToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
