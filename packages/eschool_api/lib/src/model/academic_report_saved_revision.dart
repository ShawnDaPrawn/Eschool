//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_report_saved_revision.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReportSavedRevision {
  /// Returns a new [AcademicReportSavedRevision] instance.
  AcademicReportSavedRevision({required this.id, required this.revision});

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReportSavedRevision &&
          other.id == id &&
          other.revision == revision;

  @override
  int get hashCode => id.hashCode + revision.hashCode;

  factory AcademicReportSavedRevision.fromJson(Map<String, dynamic> json) =>
      _$AcademicReportSavedRevisionFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReportSavedRevisionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
