//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_bell_period_write.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_bells_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicBellsWrite {
  /// Returns a new [AcademicBellsWrite] instance.
  AcademicBellsWrite({
    required this.name,

    required this.section,

    required this.sessionYearId,

    required this.periods,

    this.revision,
  });

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'section', required: true, includeIfNull: false)
  final String section;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'periods', required: true, includeIfNull: false)
  final List<AcademicBellPeriodWrite> periods;

  // minimum: 1
  @JsonKey(name: r'revision', required: false, includeIfNull: false)
  final int? revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicBellsWrite &&
          other.name == name &&
          other.section == section &&
          other.sessionYearId == sessionYearId &&
          other.periods == periods &&
          other.revision == revision;

  @override
  int get hashCode =>
      name.hashCode +
      section.hashCode +
      sessionYearId.hashCode +
      periods.hashCode +
      revision.hashCode;

  factory AcademicBellsWrite.fromJson(Map<String, dynamic> json) =>
      _$AcademicBellsWriteFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicBellsWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
