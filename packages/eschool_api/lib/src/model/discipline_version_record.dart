//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/discipline_record_basis.dart';
import 'package:json_annotation/json_annotation.dart';

part 'discipline_version_record.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DisciplineVersionRecord {
  /// Returns a new [DisciplineVersionRecord] instance.
  DisciplineVersionRecord({
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

    required this.description,

    required this.staffNote,

    required this.decisionReason,

    required this.familyReason,

    required this.basis,

    required this.event,
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
    unknownEnumValue: DisciplineVersionRecordStatusEnum.unknownDefaultOpenApi,
  )
  final DisciplineVersionRecordStatusEnum status;

  @JsonKey(
    name: r'hearing_decision',
    required: true,
    includeIfNull: true,
    unknownEnumValue:
        DisciplineVersionRecordHearingDecisionEnum.unknownDefaultOpenApi,
  )
  final DisciplineVersionRecordHearingDecisionEnum? hearingDecision;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'published', required: true, includeIfNull: false)
  final bool published;

  @JsonKey(name: r'description', required: true, includeIfNull: false)
  final String description;

  @JsonKey(name: r'staff_note', required: true, includeIfNull: true)
  final String? staffNote;

  @JsonKey(name: r'decision_reason', required: true, includeIfNull: true)
  final String? decisionReason;

  @JsonKey(name: r'family_reason', required: true, includeIfNull: true)
  final String? familyReason;

  @JsonKey(name: r'basis', required: true, includeIfNull: false)
  final DisciplineRecordBasis basis;

  @JsonKey(
    name: r'event',
    required: true,
    includeIfNull: false,
    unknownEnumValue: DisciplineVersionRecordEventEnum.unknownDefaultOpenApi,
  )
  final DisciplineVersionRecordEventEnum event;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DisciplineVersionRecord &&
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
          other.description == description &&
          other.staffNote == staffNote &&
          other.decisionReason == decisionReason &&
          other.familyReason == familyReason &&
          other.basis == basis &&
          other.event == event;

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
      description.hashCode +
      (staffNote == null ? 0 : staffNote.hashCode) +
      (decisionReason == null ? 0 : decisionReason.hashCode) +
      (familyReason == null ? 0 : familyReason.hashCode) +
      basis.hashCode +
      event.hashCode;

  factory DisciplineVersionRecord.fromJson(Map<String, dynamic> json) =>
      _$DisciplineVersionRecordFromJson(json);

  Map<String, dynamic> toJson() => _$DisciplineVersionRecordToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum DisciplineVersionRecordStatusEnum {
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

  const DisciplineVersionRecordStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum DisciplineVersionRecordHearingDecisionEnum {
  @JsonValue(r'no_action')
  noAction(r'no_action'),
  @JsonValue(r'sanction')
  sanction(r'sanction'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const DisciplineVersionRecordHearingDecisionEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum DisciplineVersionRecordEventEnum {
  @JsonValue(r'recorded')
  recorded(r'recorded'),
  @JsonValue(r'confirm')
  confirm(r'confirm'),
  @JsonValue(r'overturn')
  overturn(r'overturn'),
  @JsonValue(r'hearing_resolved')
  hearingResolved(r'hearing_resolved'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const DisciplineVersionRecordEventEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
