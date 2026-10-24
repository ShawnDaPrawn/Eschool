//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_bell_period.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_bells.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicBells {
  /// Returns a new [AcademicBells] instance.
  AcademicBells({
    required this.name,

    required this.section,

    required this.sessionYearId,

    required this.periods,

    required this.id,

    required this.revision,
  });

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'section', required: true, includeIfNull: false)
  final String section;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'periods', required: true, includeIfNull: false)
  final List<AcademicBellPeriod> periods;

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicBells &&
          other.name == name &&
          other.section == section &&
          other.sessionYearId == sessionYearId &&
          other.periods == periods &&
          other.id == id &&
          other.revision == revision;

  @override
  int get hashCode =>
      name.hashCode +
      section.hashCode +
      sessionYearId.hashCode +
      periods.hashCode +
      id.hashCode +
      revision.hashCode;

  factory AcademicBells.fromJson(Map<String, dynamic> json) =>
      _$AcademicBellsFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicBellsToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
