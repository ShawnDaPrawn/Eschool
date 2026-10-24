//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_mark.dart';
import 'package:eschool_api/src/model/academic_mark_result.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_matrix_learner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicMatrixLearner {
  /// Returns a new [AcademicMatrixLearner] instance.
  AcademicMatrixLearner({
    required this.learnerId,

    required this.studentNumber,

    required this.displayName,

    required this.marks,

    required this.result,
  });

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'student_number', required: true, includeIfNull: true)
  final String? studentNumber;

  @JsonKey(name: r'display_name', required: true, includeIfNull: false)
  final String displayName;

  @JsonKey(name: r'marks', required: true, includeIfNull: false)
  final List<AcademicMark> marks;

  @JsonKey(name: r'result', required: true, includeIfNull: false)
  final AcademicMarkResult result;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicMatrixLearner &&
          other.learnerId == learnerId &&
          other.studentNumber == studentNumber &&
          other.displayName == displayName &&
          other.marks == marks &&
          other.result == result;

  @override
  int get hashCode =>
      learnerId.hashCode +
      (studentNumber == null ? 0 : studentNumber.hashCode) +
      displayName.hashCode +
      marks.hashCode +
      result.hashCode;

  factory AcademicMatrixLearner.fromJson(Map<String, dynamic> json) =>
      _$AcademicMatrixLearnerFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicMatrixLearnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
