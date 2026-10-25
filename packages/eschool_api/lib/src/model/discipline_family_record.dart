//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/discipline_family_detention.dart';
import 'package:json_annotation/json_annotation.dart';

part 'discipline_family_record.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DisciplineFamilyRecord {
  /// Returns a new [DisciplineFamilyRecord] instance.
  DisciplineFamilyRecord({
    required this.id,

    required this.occurredOn,

    required this.description,

    required this.infraction,

    required this.level,

    required this.sanction,

    required this.status,

    required this.familyReason,

    required this.revision,

    required this.acknowledged,

    required this.detentions,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'occurred_on', required: true, includeIfNull: false)
  final String occurredOn;

  @JsonKey(name: r'description', required: true, includeIfNull: false)
  final String description;

  @JsonKey(name: r'infraction', required: true, includeIfNull: false)
  final String infraction;

  @JsonKey(name: r'level', required: true, includeIfNull: false)
  final String level;

  @JsonKey(name: r'sanction', required: true, includeIfNull: true)
  final String? sanction;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: DisciplineFamilyRecordStatusEnum.unknownDefaultOpenApi,
  )
  final DisciplineFamilyRecordStatusEnum status;

  @JsonKey(name: r'family_reason', required: true, includeIfNull: true)
  final String? familyReason;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'acknowledged', required: true, includeIfNull: false)
  final bool acknowledged;

  @JsonKey(name: r'detentions', required: true, includeIfNull: false)
  final List<DisciplineFamilyDetention> detentions;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DisciplineFamilyRecord &&
          other.id == id &&
          other.occurredOn == occurredOn &&
          other.description == description &&
          other.infraction == infraction &&
          other.level == level &&
          other.sanction == sanction &&
          other.status == status &&
          other.familyReason == familyReason &&
          other.revision == revision &&
          other.acknowledged == acknowledged &&
          other.detentions == detentions;

  @override
  int get hashCode =>
      id.hashCode +
      occurredOn.hashCode +
      description.hashCode +
      infraction.hashCode +
      level.hashCode +
      (sanction == null ? 0 : sanction.hashCode) +
      status.hashCode +
      (familyReason == null ? 0 : familyReason.hashCode) +
      revision.hashCode +
      acknowledged.hashCode +
      detentions.hashCode;

  factory DisciplineFamilyRecord.fromJson(Map<String, dynamic> json) =>
      _$DisciplineFamilyRecordFromJson(json);

  Map<String, dynamic> toJson() => _$DisciplineFamilyRecordToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum DisciplineFamilyRecordStatusEnum {
  @JsonValue(r'pending_review')
  pendingReview(r'pending_review'),
  @JsonValue(r'confirmed')
  confirmed(r'confirmed'),
  @JsonValue(r'hearing')
  hearing(r'hearing'),
  @JsonValue(r'overturned')
  overturned(r'overturned'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const DisciplineFamilyRecordStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
