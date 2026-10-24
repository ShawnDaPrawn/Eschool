//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_component_result.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicComponentResult {
  /// Returns a new [AcademicComponentResult] instance.
  AcademicComponentResult({
    required this.weightBp,

    required this.declaredTaskWeightBp,

    required this.excusedTaskWeightBp,

    required this.knownTaskWeightBp,

    required this.runningBp,

    required this.percentageBp,

    required this.complete,
  });

  @JsonKey(name: r'weight_bp', required: true, includeIfNull: false)
  final int weightBp;

  @JsonKey(
    name: r'declared_task_weight_bp',
    required: true,
    includeIfNull: false,
  )
  final int declaredTaskWeightBp;

  @JsonKey(
    name: r'excused_task_weight_bp',
    required: true,
    includeIfNull: false,
  )
  final int excusedTaskWeightBp;

  @JsonKey(name: r'known_task_weight_bp', required: true, includeIfNull: false)
  final int knownTaskWeightBp;

  // minimum: 0
  @JsonKey(name: r'running_bp', required: true, includeIfNull: true)
  final int? runningBp;

  // minimum: 0
  @JsonKey(name: r'percentage_bp', required: true, includeIfNull: true)
  final int? percentageBp;

  @JsonKey(name: r'complete', required: true, includeIfNull: false)
  final bool complete;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicComponentResult &&
          other.weightBp == weightBp &&
          other.declaredTaskWeightBp == declaredTaskWeightBp &&
          other.excusedTaskWeightBp == excusedTaskWeightBp &&
          other.knownTaskWeightBp == knownTaskWeightBp &&
          other.runningBp == runningBp &&
          other.percentageBp == percentageBp &&
          other.complete == complete;

  @override
  int get hashCode =>
      weightBp.hashCode +
      declaredTaskWeightBp.hashCode +
      excusedTaskWeightBp.hashCode +
      knownTaskWeightBp.hashCode +
      (runningBp == null ? 0 : runningBp.hashCode) +
      (percentageBp == null ? 0 : percentageBp.hashCode) +
      complete.hashCode;

  factory AcademicComponentResult.fromJson(Map<String, dynamic> json) =>
      _$AcademicComponentResultFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicComponentResultToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
