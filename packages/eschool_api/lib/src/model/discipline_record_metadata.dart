//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'discipline_record_metadata.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DisciplineRecordMetadata {
  /// Returns a new [DisciplineRecordMetadata] instance.
  DisciplineRecordMetadata({
    required this.id,

    required this.learnerId,

    required this.sessionYearId,

    required this.gradeLevelId,

    required this.recordedBy,

    required this.occurredOn,

    required this.infractionCode,

    required this.levelCode,

    required this.sanctionCode,

    required this.status,

    required this.hearingDecision,

    required this.revision,

    required this.published,

    this.learnerName,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  // minimum: 1
  @JsonKey(name: r'grade_level_id', required: true, includeIfNull: false)
  final int gradeLevelId;

  // minimum: 1
  @JsonKey(name: r'recorded_by', required: true, includeIfNull: false)
  final int recordedBy;

  @JsonKey(name: r'occurred_on', required: true, includeIfNull: false)
  final String occurredOn;

  @JsonKey(name: r'infraction_code', required: true, includeIfNull: false)
  final String infractionCode;

  @JsonKey(name: r'level_code', required: true, includeIfNull: false)
  final String levelCode;

  @JsonKey(name: r'sanction_code', required: true, includeIfNull: true)
  final String? sanctionCode;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: DisciplineRecordMetadataStatusEnum.unknownDefaultOpenApi,
  )
  final DisciplineRecordMetadataStatusEnum status;

  @JsonKey(
    name: r'hearing_decision',
    required: true,
    includeIfNull: true,
    unknownEnumValue:
        DisciplineRecordMetadataHearingDecisionEnum.unknownDefaultOpenApi,
  )
  final DisciplineRecordMetadataHearingDecisionEnum? hearingDecision;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'published', required: true, includeIfNull: false)
  final bool published;

  @JsonKey(name: r'learner_name', required: false, includeIfNull: false)
  final String? learnerName;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DisciplineRecordMetadata &&
          other.id == id &&
          other.learnerId == learnerId &&
          other.sessionYearId == sessionYearId &&
          other.gradeLevelId == gradeLevelId &&
          other.recordedBy == recordedBy &&
          other.occurredOn == occurredOn &&
          other.infractionCode == infractionCode &&
          other.levelCode == levelCode &&
          other.sanctionCode == sanctionCode &&
          other.status == status &&
          other.hearingDecision == hearingDecision &&
          other.revision == revision &&
          other.published == published &&
          other.learnerName == learnerName;

  @override
  int get hashCode =>
      id.hashCode +
      learnerId.hashCode +
      sessionYearId.hashCode +
      gradeLevelId.hashCode +
      recordedBy.hashCode +
      occurredOn.hashCode +
      infractionCode.hashCode +
      levelCode.hashCode +
      (sanctionCode == null ? 0 : sanctionCode.hashCode) +
      status.hashCode +
      (hearingDecision == null ? 0 : hearingDecision.hashCode) +
      revision.hashCode +
      published.hashCode +
      learnerName.hashCode;

  factory DisciplineRecordMetadata.fromJson(Map<String, dynamic> json) =>
      _$DisciplineRecordMetadataFromJson(json);

  Map<String, dynamic> toJson() => _$DisciplineRecordMetadataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum DisciplineRecordMetadataStatusEnum {
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

  const DisciplineRecordMetadataStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum DisciplineRecordMetadataHearingDecisionEnum {
  @JsonValue(r'no_action')
  noAction(r'no_action'),
  @JsonValue(r'sanction')
  sanction(r'sanction'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const DisciplineRecordMetadataHearingDecisionEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
