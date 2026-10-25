//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/discipline_choice.dart';
import 'package:eschool_api/src/model/discipline_optional_framework.dart';
import 'package:json_annotation/json_annotation.dart';

part 'discipline_configuration.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DisciplineConfiguration {
  /// Returns a new [DisciplineConfiguration] instance.
  DisciplineConfiguration({
    required this.sessionYearId,

    required this.today,

    required this.canManage,

    required this.canHear,

    required this.gradeIds,

    required this.grades,

    required this.years,

    required this.framework,
  });

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'today', required: true, includeIfNull: false)
  final String today;

  @JsonKey(name: r'can_manage', required: true, includeIfNull: false)
  final bool canManage;

  @JsonKey(name: r'can_hear', required: true, includeIfNull: false)
  final bool canHear;

  @JsonKey(name: r'grade_ids', required: true, includeIfNull: false)
  final List<int> gradeIds;

  @JsonKey(name: r'grades', required: true, includeIfNull: false)
  final List<DisciplineChoice> grades;

  @JsonKey(name: r'years', required: true, includeIfNull: false)
  final List<DisciplineChoice> years;

  @JsonKey(name: r'framework', required: true, includeIfNull: true)
  final DisciplineOptionalFramework? framework;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DisciplineConfiguration &&
          other.sessionYearId == sessionYearId &&
          other.today == today &&
          other.canManage == canManage &&
          other.canHear == canHear &&
          other.gradeIds == gradeIds &&
          other.grades == grades &&
          other.years == years &&
          other.framework == framework;

  @override
  int get hashCode =>
      sessionYearId.hashCode +
      today.hashCode +
      canManage.hashCode +
      canHear.hashCode +
      gradeIds.hashCode +
      grades.hashCode +
      years.hashCode +
      (framework == null ? 0 : framework.hashCode);

  factory DisciplineConfiguration.fromJson(Map<String, dynamic> json) =>
      _$DisciplineConfigurationFromJson(json);

  Map<String, dynamic> toJson() => _$DisciplineConfigurationToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
