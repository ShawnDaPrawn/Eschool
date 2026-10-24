//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_task_result.dart';
import 'package:eschool_api/src/model/academic_component_result.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_mark_result.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicMarkResult {
  /// Returns a new [AcademicMarkResult] instance.
  AcademicMarkResult({
    required this.percentageBp,

    required this.runningBp,

    required this.coverageBp,

    required this.ratingLevel,

    required this.runningRatingLevel,

    required this.complete,

    required this.provisional,

    required this.components,

    required this.tasks,

    required this.warnings,

    required this.rounding,

    required this.absentPolicy,
  });

  // minimum: 0
  @JsonKey(name: r'percentage_bp', required: true, includeIfNull: true)
  final int? percentageBp;

  // minimum: 0
  @JsonKey(name: r'running_bp', required: true, includeIfNull: true)
  final int? runningBp;

  @JsonKey(name: r'coverage_bp', required: true, includeIfNull: false)
  final int coverageBp;

  // minimum: 0
  @JsonKey(name: r'rating_level', required: true, includeIfNull: true)
  final int? ratingLevel;

  // minimum: 0
  @JsonKey(name: r'running_rating_level', required: true, includeIfNull: true)
  final int? runningRatingLevel;

  @JsonKey(name: r'complete', required: true, includeIfNull: false)
  final bool complete;

  @JsonKey(name: r'provisional', required: true, includeIfNull: false)
  final bool provisional;

  @JsonKey(name: r'components', required: true, includeIfNull: false)
  final Map<String, AcademicComponentResult> components;

  @JsonKey(name: r'tasks', required: true, includeIfNull: false)
  final List<AcademicTaskResult> tasks;

  @JsonKey(name: r'warnings', required: true, includeIfNull: false)
  final List<String> warnings;

  @JsonKey(name: r'rounding', required: true, includeIfNull: false)
  final String rounding;

  @JsonKey(name: r'absent_policy', required: true, includeIfNull: false)
  final String absentPolicy;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicMarkResult &&
          other.percentageBp == percentageBp &&
          other.runningBp == runningBp &&
          other.coverageBp == coverageBp &&
          other.ratingLevel == ratingLevel &&
          other.runningRatingLevel == runningRatingLevel &&
          other.complete == complete &&
          other.provisional == provisional &&
          other.components == components &&
          other.tasks == tasks &&
          other.warnings == warnings &&
          other.rounding == rounding &&
          other.absentPolicy == absentPolicy;

  @override
  int get hashCode =>
      (percentageBp == null ? 0 : percentageBp.hashCode) +
      (runningBp == null ? 0 : runningBp.hashCode) +
      coverageBp.hashCode +
      (ratingLevel == null ? 0 : ratingLevel.hashCode) +
      (runningRatingLevel == null ? 0 : runningRatingLevel.hashCode) +
      complete.hashCode +
      provisional.hashCode +
      components.hashCode +
      tasks.hashCode +
      warnings.hashCode +
      rounding.hashCode +
      absentPolicy.hashCode;

  factory AcademicMarkResult.fromJson(Map<String, dynamic> json) =>
      _$AcademicMarkResultFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicMarkResultToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
