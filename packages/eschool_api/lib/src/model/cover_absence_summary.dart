//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'cover_absence_summary.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CoverAbsenceSummary {
  /// Returns a new [CoverAbsenceSummary] instance.
  CoverAbsenceSummary({
    required this.id,

    required this.revision,

    required this.state,

    required this.teacherId,

    required this.teacherName,

    required this.startsOn,

    required this.endsOn,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: CoverAbsenceSummaryStateEnum.unknownDefaultOpenApi,
  )
  final CoverAbsenceSummaryStateEnum state;

  // minimum: 1
  @JsonKey(name: r'teacher_id', required: true, includeIfNull: false)
  final int teacherId;

  @JsonKey(name: r'teacher_name', required: true, includeIfNull: false)
  final String teacherName;

  @JsonKey(name: r'starts_on', required: true, includeIfNull: false)
  final String startsOn;

  @JsonKey(name: r'ends_on', required: true, includeIfNull: false)
  final String endsOn;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CoverAbsenceSummary &&
          other.id == id &&
          other.revision == revision &&
          other.state == state &&
          other.teacherId == teacherId &&
          other.teacherName == teacherName &&
          other.startsOn == startsOn &&
          other.endsOn == endsOn;

  @override
  int get hashCode =>
      id.hashCode +
      revision.hashCode +
      state.hashCode +
      teacherId.hashCode +
      teacherName.hashCode +
      startsOn.hashCode +
      endsOn.hashCode;

  factory CoverAbsenceSummary.fromJson(Map<String, dynamic> json) =>
      _$CoverAbsenceSummaryFromJson(json);

  Map<String, dynamic> toJson() => _$CoverAbsenceSummaryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum CoverAbsenceSummaryStateEnum {
  @JsonValue(r'requested')
  requested(r'requested'),
  @JsonValue(r'cancelled')
  cancelled(r'cancelled'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const CoverAbsenceSummaryStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
