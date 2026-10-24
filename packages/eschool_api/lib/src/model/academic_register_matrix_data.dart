//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_register_learner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_register_matrix_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicRegisterMatrixData {
  /// Returns a new [AcademicRegisterMatrixData] instance.
  AcademicRegisterMatrixData({
    required this.registerId,

    required this.label,

    required this.sessionYearId,

    required this.classSectionId,

    required this.timetableLessonId,

    required this.onDate,

    required this.revision,

    required this.rosterHash,

    required this.canWrite,

    required this.learners,
  });

  // minimum: 1
  @JsonKey(name: r'register_id', required: true, includeIfNull: false)
  final int registerId;

  @JsonKey(name: r'label', required: true, includeIfNull: false)
  final String label;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  // minimum: 1
  @JsonKey(name: r'class_section_id', required: true, includeIfNull: true)
  final int? classSectionId;

  // minimum: 1
  @JsonKey(name: r'timetable_lesson_id', required: true, includeIfNull: true)
  final int? timetableLessonId;

  @JsonKey(name: r'on_date', required: true, includeIfNull: false)
  final String onDate;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'roster_hash', required: true, includeIfNull: false)
  final String rosterHash;

  @JsonKey(name: r'can_write', required: true, includeIfNull: false)
  final bool canWrite;

  @JsonKey(name: r'learners', required: true, includeIfNull: false)
  final List<AcademicRegisterLearner> learners;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicRegisterMatrixData &&
          other.registerId == registerId &&
          other.label == label &&
          other.sessionYearId == sessionYearId &&
          other.classSectionId == classSectionId &&
          other.timetableLessonId == timetableLessonId &&
          other.onDate == onDate &&
          other.revision == revision &&
          other.rosterHash == rosterHash &&
          other.canWrite == canWrite &&
          other.learners == learners;

  @override
  int get hashCode =>
      registerId.hashCode +
      label.hashCode +
      sessionYearId.hashCode +
      (classSectionId == null ? 0 : classSectionId.hashCode) +
      (timetableLessonId == null ? 0 : timetableLessonId.hashCode) +
      onDate.hashCode +
      revision.hashCode +
      rosterHash.hashCode +
      canWrite.hashCode +
      learners.hashCode;

  factory AcademicRegisterMatrixData.fromJson(Map<String, dynamic> json) =>
      _$AcademicRegisterMatrixDataFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicRegisterMatrixDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
