// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_record_metadata.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineRecordMetadata _$DisciplineRecordMetadataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'DisciplineRecordMetadata',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'learner_id',
        'session_year_id',
        'grade_level_id',
        'recorded_by',
        'occurred_on',
        'infraction_code',
        'level_code',
        'sanction_code',
        'status',
        'hearing_decision',
        'revision',
        'published',
      ],
    );
    final val = DisciplineRecordMetadata(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      gradeLevelId: $checkedConvert(
        'grade_level_id',
        (v) => (v as num).toInt(),
      ),
      recordedBy: $checkedConvert('recorded_by', (v) => (v as num).toInt()),
      occurredOn: $checkedConvert('occurred_on', (v) => v as String),
      infractionCode: $checkedConvert('infraction_code', (v) => v as String),
      levelCode: $checkedConvert('level_code', (v) => v as String),
      sanctionCode: $checkedConvert('sanction_code', (v) => v as String?),
      status: $checkedConvert(
        'status',
        (v) => $enumDecode(
          _$DisciplineRecordMetadataStatusEnumEnumMap,
          v,
          unknownValue:
              DisciplineRecordMetadataStatusEnum.unknownDefaultOpenApi,
        ),
      ),
      hearingDecision: $checkedConvert(
        'hearing_decision',
        (v) => $enumDecodeNullable(
          _$DisciplineRecordMetadataHearingDecisionEnumEnumMap,
          v,
          unknownValue:
              DisciplineRecordMetadataHearingDecisionEnum.unknownDefaultOpenApi,
        ),
      ),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      published: $checkedConvert('published', (v) => v as bool),
      learnerName: $checkedConvert('learner_name', (v) => v as String?),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'sessionYearId': 'session_year_id',
    'gradeLevelId': 'grade_level_id',
    'recordedBy': 'recorded_by',
    'occurredOn': 'occurred_on',
    'infractionCode': 'infraction_code',
    'levelCode': 'level_code',
    'sanctionCode': 'sanction_code',
    'hearingDecision': 'hearing_decision',
    'learnerName': 'learner_name',
  },
);

Map<String, dynamic> _$DisciplineRecordMetadataToJson(
  DisciplineRecordMetadata instance,
) => <String, dynamic>{
  'id': instance.id,
  'learner_id': instance.learnerId,
  'session_year_id': instance.sessionYearId,
  'grade_level_id': instance.gradeLevelId,
  'recorded_by': instance.recordedBy,
  'occurred_on': instance.occurredOn,
  'infraction_code': instance.infractionCode,
  'level_code': instance.levelCode,
  'sanction_code': instance.sanctionCode,
  'status': _$DisciplineRecordMetadataStatusEnumEnumMap[instance.status]!,
  'hearing_decision':
      _$DisciplineRecordMetadataHearingDecisionEnumEnumMap[instance
          .hearingDecision],
  'revision': instance.revision,
  'published': instance.published,
  'learner_name': ?instance.learnerName,
};

const _$DisciplineRecordMetadataStatusEnumEnumMap = {
  DisciplineRecordMetadataStatusEnum.pendingReview: 'pending_review',
  DisciplineRecordMetadataStatusEnum.confirmed: 'confirmed',
  DisciplineRecordMetadataStatusEnum.hearing: 'hearing',
  DisciplineRecordMetadataStatusEnum.overturned: 'overturned',
  DisciplineRecordMetadataStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$DisciplineRecordMetadataHearingDecisionEnumEnumMap = {
  DisciplineRecordMetadataHearingDecisionEnum.noAction: 'no_action',
  DisciplineRecordMetadataHearingDecisionEnum.sanction: 'sanction',
  DisciplineRecordMetadataHearingDecisionEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
