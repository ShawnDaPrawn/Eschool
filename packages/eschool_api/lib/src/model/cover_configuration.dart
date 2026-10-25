//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/cover_teacher.dart';
import 'package:eschool_api/src/model/cover_absence_summary.dart';
import 'package:json_annotation/json_annotation.dart';

part 'cover_configuration.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CoverConfiguration {
  /// Returns a new [CoverConfiguration] instance.
  CoverConfiguration({
    required this.sessionYearId,

    required this.yearName,

    required this.canManage,

    required this.teachers,

    required this.currentPage,

    required this.lastPage,

    required this.total,

    required this.absences,
  });

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'year_name', required: true, includeIfNull: false)
  final String yearName;

  @JsonKey(name: r'can_manage', required: true, includeIfNull: false)
  final bool canManage;

  @JsonKey(name: r'teachers', required: true, includeIfNull: false)
  final List<CoverTeacher> teachers;

  // minimum: 1
  @JsonKey(name: r'current_page', required: true, includeIfNull: false)
  final int currentPage;

  // minimum: 1
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  // minimum: 0
  @JsonKey(name: r'total', required: true, includeIfNull: false)
  final int total;

  @JsonKey(name: r'absences', required: true, includeIfNull: false)
  final List<CoverAbsenceSummary> absences;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CoverConfiguration &&
          other.sessionYearId == sessionYearId &&
          other.yearName == yearName &&
          other.canManage == canManage &&
          other.teachers == teachers &&
          other.currentPage == currentPage &&
          other.lastPage == lastPage &&
          other.total == total &&
          other.absences == absences;

  @override
  int get hashCode =>
      sessionYearId.hashCode +
      yearName.hashCode +
      canManage.hashCode +
      teachers.hashCode +
      currentPage.hashCode +
      lastPage.hashCode +
      total.hashCode +
      absences.hashCode;

  factory CoverConfiguration.fromJson(Map<String, dynamic> json) =>
      _$CoverConfigurationFromJson(json);

  Map<String, dynamic> toJson() => _$CoverConfigurationToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
