//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_task_result.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicTaskResult {
  /// Returns a new [AcademicTaskResult] instance.
  AcademicTaskResult({
    required this.taskId,

    required this.percentageBp,

    required this.disposition,

    required this.complete,

    required this.weightBp,
  });

  // minimum: 1
  @JsonKey(name: r'task_id', required: true, includeIfNull: false)
  final int taskId;

  // minimum: 0
  @JsonKey(name: r'percentage_bp', required: true, includeIfNull: true)
  final int? percentageBp;

  @JsonKey(name: r'disposition', required: true, includeIfNull: false)
  final String disposition;

  @JsonKey(name: r'complete', required: true, includeIfNull: false)
  final bool complete;

  @JsonKey(name: r'weight_bp', required: true, includeIfNull: false)
  final int weightBp;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicTaskResult &&
          other.taskId == taskId &&
          other.percentageBp == percentageBp &&
          other.disposition == disposition &&
          other.complete == complete &&
          other.weightBp == weightBp;

  @override
  int get hashCode =>
      taskId.hashCode +
      (percentageBp == null ? 0 : percentageBp.hashCode) +
      disposition.hashCode +
      complete.hashCode +
      weightBp.hashCode;

  factory AcademicTaskResult.fromJson(Map<String, dynamic> json) =>
      _$AcademicTaskResultFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicTaskResultToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
