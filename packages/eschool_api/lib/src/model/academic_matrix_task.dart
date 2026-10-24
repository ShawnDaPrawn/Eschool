//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_task_question.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_matrix_task.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicMatrixTask {
  /// Returns a new [AcademicMatrixTask] instance.
  AcademicMatrixTask({
    required this.id,

    required this.canonicalTaskId,

    required this.name,

    required this.maxMark,

    required this.component,

    required this.weightBp,

    required this.questions,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'canonical_task_id', required: true, includeIfNull: false)
  final int canonicalTaskId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'max_mark', required: true, includeIfNull: false)
  final String maxMark;

  @JsonKey(name: r'component', required: true, includeIfNull: false)
  final String component;

  @JsonKey(name: r'weight_bp', required: true, includeIfNull: false)
  final int weightBp;

  @JsonKey(name: r'questions', required: true, includeIfNull: false)
  final List<AcademicTaskQuestion> questions;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicMatrixTask &&
          other.id == id &&
          other.canonicalTaskId == canonicalTaskId &&
          other.name == name &&
          other.maxMark == maxMark &&
          other.component == component &&
          other.weightBp == weightBp &&
          other.questions == questions;

  @override
  int get hashCode =>
      id.hashCode +
      canonicalTaskId.hashCode +
      name.hashCode +
      maxMark.hashCode +
      component.hashCode +
      weightBp.hashCode +
      questions.hashCode;

  factory AcademicMatrixTask.fromJson(Map<String, dynamic> json) =>
      _$AcademicMatrixTaskFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicMatrixTaskToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
