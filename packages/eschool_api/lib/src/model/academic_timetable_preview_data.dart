//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_timetable_clash.dart';
import 'package:eschool_api/src/model/academic_lesson_write.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_timetable_preview_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicTimetablePreviewData {
  /// Returns a new [AcademicTimetablePreviewData] instance.
  AcademicTimetablePreviewData({
    required this.rows,

    required this.conflicts,

    required this.canSave,

    required this.fingerprint,

    this.importedRows,
  });

  @JsonKey(name: r'rows', required: true, includeIfNull: false)
  final List<AcademicLessonWrite> rows;

  @JsonKey(name: r'conflicts', required: true, includeIfNull: false)
  final List<AcademicTimetableClash> conflicts;

  @JsonKey(name: r'can_save', required: true, includeIfNull: false)
  final bool canSave;

  @JsonKey(name: r'fingerprint', required: true, includeIfNull: false)
  final String fingerprint;

  // minimum: 1
  @JsonKey(name: r'imported_rows', required: false, includeIfNull: false)
  final int? importedRows;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicTimetablePreviewData &&
          other.rows == rows &&
          other.conflicts == conflicts &&
          other.canSave == canSave &&
          other.fingerprint == fingerprint &&
          other.importedRows == importedRows;

  @override
  int get hashCode =>
      rows.hashCode +
      conflicts.hashCode +
      canSave.hashCode +
      fingerprint.hashCode +
      importedRows.hashCode;

  factory AcademicTimetablePreviewData.fromJson(Map<String, dynamic> json) =>
      _$AcademicTimetablePreviewDataFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicTimetablePreviewDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
