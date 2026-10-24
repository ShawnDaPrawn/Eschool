//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_stored_question.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicStoredQuestion {
  /// Returns a new [AcademicStoredQuestion] instance.
  AcademicStoredQuestion({
    required this.canonicalQuestionId,

    required this.mark,
  });

  // minimum: 1
  @JsonKey(name: r'canonical_question_id', required: true, includeIfNull: false)
  final int canonicalQuestionId;

  @JsonKey(name: r'mark', required: true, includeIfNull: true)
  final String? mark;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicStoredQuestion &&
          other.canonicalQuestionId == canonicalQuestionId &&
          other.mark == mark;

  @override
  int get hashCode =>
      canonicalQuestionId.hashCode + (mark == null ? 0 : mark.hashCode);

  factory AcademicStoredQuestion.fromJson(Map<String, dynamic> json) =>
      _$AcademicStoredQuestionFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicStoredQuestionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
