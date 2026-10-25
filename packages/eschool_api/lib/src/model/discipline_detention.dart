//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'discipline_detention.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DisciplineDetention {
  /// Returns a new [DisciplineDetention] instance.
  DisciplineDetention({
    required this.id,

    required this.sessionYearId,

    required this.gradeLevelId,

    required this.supervisorId,

    required this.onDate,

    required this.startsAt,

    required this.endsAt,

    required this.timezone,

    required this.capacity,

    required this.status,

    required this.revision,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  // minimum: 1
  @JsonKey(name: r'grade_level_id', required: true, includeIfNull: false)
  final int gradeLevelId;

  // minimum: 1
  @JsonKey(name: r'supervisor_id', required: true, includeIfNull: false)
  final int supervisorId;

  @JsonKey(name: r'on_date', required: true, includeIfNull: false)
  final String onDate;

  @JsonKey(name: r'starts_at', required: true, includeIfNull: false)
  final String startsAt;

  @JsonKey(name: r'ends_at', required: true, includeIfNull: false)
  final String endsAt;

  @JsonKey(name: r'timezone', required: true, includeIfNull: false)
  final String timezone;

  // minimum: 1
  // maximum: 200
  @JsonKey(name: r'capacity', required: true, includeIfNull: false)
  final int capacity;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: DisciplineDetentionStatusEnum.unknownDefaultOpenApi,
  )
  final DisciplineDetentionStatusEnum status;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DisciplineDetention &&
          other.id == id &&
          other.sessionYearId == sessionYearId &&
          other.gradeLevelId == gradeLevelId &&
          other.supervisorId == supervisorId &&
          other.onDate == onDate &&
          other.startsAt == startsAt &&
          other.endsAt == endsAt &&
          other.timezone == timezone &&
          other.capacity == capacity &&
          other.status == status &&
          other.revision == revision;

  @override
  int get hashCode =>
      id.hashCode +
      sessionYearId.hashCode +
      gradeLevelId.hashCode +
      supervisorId.hashCode +
      onDate.hashCode +
      startsAt.hashCode +
      endsAt.hashCode +
      timezone.hashCode +
      capacity.hashCode +
      status.hashCode +
      revision.hashCode;

  factory DisciplineDetention.fromJson(Map<String, dynamic> json) =>
      _$DisciplineDetentionFromJson(json);

  Map<String, dynamic> toJson() => _$DisciplineDetentionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum DisciplineDetentionStatusEnum {
  @JsonValue(r'planned')
  planned(r'planned'),
  @JsonValue(r'cancelled')
  cancelled(r'cancelled'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const DisciplineDetentionStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
