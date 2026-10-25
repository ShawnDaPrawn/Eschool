//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/discipline_detention_entry.dart';
import 'package:json_annotation/json_annotation.dart';

part 'discipline_detention_detail.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DisciplineDetentionDetail {
  /// Returns a new [DisciplineDetentionDetail] instance.
  DisciplineDetentionDetail({
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

    required this.location,

    required this.entries,
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
    unknownEnumValue: DisciplineDetentionDetailStatusEnum.unknownDefaultOpenApi,
  )
  final DisciplineDetentionDetailStatusEnum status;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'location', required: true, includeIfNull: false)
  final String location;

  @JsonKey(name: r'entries', required: true, includeIfNull: false)
  final List<DisciplineDetentionEntry> entries;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DisciplineDetentionDetail &&
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
          other.revision == revision &&
          other.location == location &&
          other.entries == entries;

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
      revision.hashCode +
      location.hashCode +
      entries.hashCode;

  factory DisciplineDetentionDetail.fromJson(Map<String, dynamic> json) =>
      _$DisciplineDetentionDetailFromJson(json);

  Map<String, dynamic> toJson() => _$DisciplineDetentionDetailToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum DisciplineDetentionDetailStatusEnum {
  @JsonValue(r'planned')
  planned(r'planned'),
  @JsonValue(r'cancelled')
  cancelled(r'cancelled'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const DisciplineDetentionDetailStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
