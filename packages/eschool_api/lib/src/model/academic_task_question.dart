//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_task_question.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicTaskQuestion {
  /// Returns a new [AcademicTaskQuestion] instance.
  AcademicTaskQuestion({
    required this.id,

    required this.canonicalQuestionId,

    required this.number,

    required this.maxMark,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'canonical_question_id', required: true, includeIfNull: false)
  final int canonicalQuestionId;

  @JsonKey(name: r'number', required: true, includeIfNull: false)
  final String number;

  @JsonKey(name: r'max_mark', required: true, includeIfNull: false)
  final String maxMark;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicTaskQuestion &&
          other.id == id &&
          other.canonicalQuestionId == canonicalQuestionId &&
          other.number == number &&
          other.maxMark == maxMark;

  @override
  int get hashCode =>
      id.hashCode +
      canonicalQuestionId.hashCode +
      number.hashCode +
      maxMark.hashCode;

  factory AcademicTaskQuestion.fromJson(Map<String, dynamic> json) =>
      _$AcademicTaskQuestionFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicTaskQuestionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
