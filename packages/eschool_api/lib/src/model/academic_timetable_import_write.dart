//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_timetable_import_defaults.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_timetable_import_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicTimetableImportWrite {
  /// Returns a new [AcademicTimetableImportWrite] instance.
  AcademicTimetableImportWrite({
    required this.csv,

    required this.sessionYearId,

    this.defaults,

    this.columns,

    this.values,
  });

  @JsonKey(name: r'csv', required: true, includeIfNull: false)
  final String csv;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'defaults', required: false, includeIfNull: false)
  final AcademicTimetableImportDefaults? defaults;

  @JsonKey(name: r'columns', required: false, includeIfNull: false)
  final Map<String, String>? columns;

  @JsonKey(name: r'values', required: false, includeIfNull: false)
  final Map<String, Map<String, Object>>? values;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicTimetableImportWrite &&
          other.csv == csv &&
          other.sessionYearId == sessionYearId &&
          other.defaults == defaults &&
          other.columns == columns &&
          other.values == values;

  @override
  int get hashCode =>
      csv.hashCode +
      sessionYearId.hashCode +
      defaults.hashCode +
      columns.hashCode +
      values.hashCode;

  factory AcademicTimetableImportWrite.fromJson(Map<String, dynamic> json) =>
      _$AcademicTimetableImportWriteFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicTimetableImportWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
