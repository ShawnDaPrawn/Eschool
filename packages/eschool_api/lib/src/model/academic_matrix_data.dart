//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_matrix_task.dart';
import 'package:eschool_api/src/model/academic_matrix_learner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_matrix_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicMatrixData {
  /// Returns a new [AcademicMatrixData] instance.
  AcademicMatrixData({
    required this.groupId,

    required this.subjectId,

    required this.gradeLevelId,

    required this.sessionYearId,

    required this.termId,

    required this.assessmentPlanId,

    required this.version,

    required this.currentPage,

    required this.lastPage,

    required this.total,

    required this.locked,

    required this.marksLockOn,

    required this.tasks,

    required this.learners,
  });

  // minimum: 1
  @JsonKey(name: r'group_id', required: true, includeIfNull: false)
  final int groupId;

  // minimum: 1
  @JsonKey(name: r'subject_id', required: true, includeIfNull: false)
  final int subjectId;

  // minimum: 1
  @JsonKey(name: r'grade_level_id', required: true, includeIfNull: false)
  final int gradeLevelId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  // minimum: 1
  @JsonKey(name: r'term_id', required: true, includeIfNull: false)
  final int termId;

  // minimum: 1
  @JsonKey(name: r'assessment_plan_id', required: true, includeIfNull: false)
  final int assessmentPlanId;

  // minimum: 1
  @JsonKey(name: r'version', required: true, includeIfNull: false)
  final int version;

  // minimum: 1
  @JsonKey(name: r'current_page', required: true, includeIfNull: false)
  final int currentPage;

  // minimum: 1
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  // minimum: 0
  @JsonKey(name: r'total', required: true, includeIfNull: false)
  final int total;

  @JsonKey(name: r'locked', required: true, includeIfNull: false)
  final bool locked;

  @JsonKey(name: r'marks_lock_on', required: true, includeIfNull: true)
  final String? marksLockOn;

  @JsonKey(name: r'tasks', required: true, includeIfNull: false)
  final List<AcademicMatrixTask> tasks;

  @JsonKey(name: r'learners', required: true, includeIfNull: false)
  final List<AcademicMatrixLearner> learners;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicMatrixData &&
          other.groupId == groupId &&
          other.subjectId == subjectId &&
          other.gradeLevelId == gradeLevelId &&
          other.sessionYearId == sessionYearId &&
          other.termId == termId &&
          other.assessmentPlanId == assessmentPlanId &&
          other.version == version &&
          other.currentPage == currentPage &&
          other.lastPage == lastPage &&
          other.total == total &&
          other.locked == locked &&
          other.marksLockOn == marksLockOn &&
          other.tasks == tasks &&
          other.learners == learners;

  @override
  int get hashCode =>
      groupId.hashCode +
      subjectId.hashCode +
      gradeLevelId.hashCode +
      sessionYearId.hashCode +
      termId.hashCode +
      assessmentPlanId.hashCode +
      version.hashCode +
      currentPage.hashCode +
      lastPage.hashCode +
      total.hashCode +
      locked.hashCode +
      (marksLockOn == null ? 0 : marksLockOn.hashCode) +
      tasks.hashCode +
      learners.hashCode;

  factory AcademicMatrixData.fromJson(Map<String, dynamic> json) =>
      _$AcademicMatrixDataFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicMatrixDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
