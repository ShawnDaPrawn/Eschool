//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_transition_grade.dart';
import 'package:eschool_api/src/model/academic_transition_homeroom.dart';
import 'package:eschool_api/src/model/academic_transition_year.dart';
import 'package:eschool_api/src/model/academic_transition_group.dart';
import 'package:eschool_api/src/model/academic_transition_retention.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_transition_configuration.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicTransitionConfiguration {
  /// Returns a new [AcademicTransitionConfiguration] instance.
  AcademicTransitionConfiguration({
    required this.sessionYearId,

    required this.schoolYears,

    required this.grades,

    required this.homerooms,

    required this.groups,

    required this.retention,
  });

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'school_years', required: true, includeIfNull: false)
  final List<AcademicTransitionYear> schoolYears;

  @JsonKey(name: r'grades', required: true, includeIfNull: false)
  final List<AcademicTransitionGrade> grades;

  @JsonKey(name: r'homerooms', required: true, includeIfNull: false)
  final List<AcademicTransitionHomeroom> homerooms;

  @JsonKey(name: r'groups', required: true, includeIfNull: false)
  final List<AcademicTransitionGroup> groups;

  @JsonKey(name: r'retention', required: true, includeIfNull: false)
  final AcademicTransitionRetention retention;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicTransitionConfiguration &&
          other.sessionYearId == sessionYearId &&
          other.schoolYears == schoolYears &&
          other.grades == grades &&
          other.homerooms == homerooms &&
          other.groups == groups &&
          other.retention == retention;

  @override
  int get hashCode =>
      sessionYearId.hashCode +
      schoolYears.hashCode +
      grades.hashCode +
      homerooms.hashCode +
      groups.hashCode +
      retention.hashCode;

  factory AcademicTransitionConfiguration.fromJson(Map<String, dynamic> json) =>
      _$AcademicTransitionConfigurationFromJson(json);

  Map<String, dynamic> toJson() =>
      _$AcademicTransitionConfigurationToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
