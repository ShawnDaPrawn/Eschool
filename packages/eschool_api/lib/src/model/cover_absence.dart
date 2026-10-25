//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/cover_occurrence.dart';
import 'package:json_annotation/json_annotation.dart';

part 'cover_absence.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CoverAbsence {
  /// Returns a new [CoverAbsence] instance.
  CoverAbsence({
    required this.id,

    required this.revision,

    required this.state,

    required this.teacherId,

    required this.teacherName,

    required this.startsOn,

    required this.endsOn,

    required this.sessionYearId,

    required this.reason,

    required this.cancelReason,

    required this.occurrences,
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
    unknownEnumValue: CoverAbsenceStateEnum.unknownDefaultOpenApi,
  )
  final CoverAbsenceStateEnum state;

  // minimum: 1
  @JsonKey(name: r'teacher_id', required: true, includeIfNull: false)
  final int teacherId;

  @JsonKey(name: r'teacher_name', required: true, includeIfNull: false)
  final String teacherName;

  @JsonKey(name: r'starts_on', required: true, includeIfNull: false)
  final String startsOn;

  @JsonKey(name: r'ends_on', required: true, includeIfNull: false)
  final String endsOn;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'reason', required: true, includeIfNull: true)
  final String? reason;

  @JsonKey(name: r'cancel_reason', required: true, includeIfNull: true)
  final String? cancelReason;

  @JsonKey(name: r'occurrences', required: true, includeIfNull: false)
  final List<CoverOccurrence> occurrences;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CoverAbsence &&
          other.id == id &&
          other.revision == revision &&
          other.state == state &&
          other.teacherId == teacherId &&
          other.teacherName == teacherName &&
          other.startsOn == startsOn &&
          other.endsOn == endsOn &&
          other.sessionYearId == sessionYearId &&
          other.reason == reason &&
          other.cancelReason == cancelReason &&
          other.occurrences == occurrences;

  @override
  int get hashCode =>
      id.hashCode +
      revision.hashCode +
      state.hashCode +
      teacherId.hashCode +
      teacherName.hashCode +
      startsOn.hashCode +
      endsOn.hashCode +
      sessionYearId.hashCode +
      (reason == null ? 0 : reason.hashCode) +
      (cancelReason == null ? 0 : cancelReason.hashCode) +
      occurrences.hashCode;

  factory CoverAbsence.fromJson(Map<String, dynamic> json) =>
      _$CoverAbsenceFromJson(json);

  Map<String, dynamic> toJson() => _$CoverAbsenceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum CoverAbsenceStateEnum {
  @JsonValue(r'requested')
  requested(r'requested'),
  @JsonValue(r'cancelled')
  cancelled(r'cancelled'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const CoverAbsenceStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
