// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_record_detail.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineRecordDetail _$DisciplineRecordDetailFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'DisciplineRecordDetail',
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
        'description',
        'staff_note',
        'decision_reason',
        'family_reason',
        'basis',
      ],
    );
    final val = DisciplineRecordDetail(
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
          _$DisciplineRecordDetailStatusEnumEnumMap,
          v,
          unknownValue: DisciplineRecordDetailStatusEnum.unknownDefaultOpenApi,
        ),
      ),
      hearingDecision: $checkedConvert(
        'hearing_decision',
        (v) => $enumDecodeNullable(
          _$DisciplineRecordDetailHearingDecisionEnumEnumMap,
          v,
          unknownValue:
              DisciplineRecordDetailHearingDecisionEnum.unknownDefaultOpenApi,
        ),
      ),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      published: $checkedConvert('published', (v) => v as bool),
      description: $checkedConvert('description', (v) => v as String),
      staffNote: $checkedConvert('staff_note', (v) => v as String?),
      decisionReason: $checkedConvert('decision_reason', (v) => v as String?),
      familyReason: $checkedConvert('family_reason', (v) => v as String?),
      basis: $checkedConvert(
        'basis',
        (v) => DisciplineRecordBasis.fromJson(v as Map<String, dynamic>),
      ),
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
    'staffNote': 'staff_note',
    'decisionReason': 'decision_reason',
    'familyReason': 'family_reason',
  },
);

Map<String, dynamic> _$DisciplineRecordDetailToJson(
  DisciplineRecordDetail instance,
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
  'status': _$DisciplineRecordDetailStatusEnumEnumMap[instance.status]!,
  'hearing_decision':
      _$DisciplineRecordDetailHearingDecisionEnumEnumMap[instance
          .hearingDecision],
  'revision': instance.revision,
  'published': instance.published,
  'description': instance.description,
  'staff_note': instance.staffNote,
  'decision_reason': instance.decisionReason,
  'family_reason': instance.familyReason,
  'basis': instance.basis.toJson(),
};

const _$DisciplineRecordDetailStatusEnumEnumMap = {
  DisciplineRecordDetailStatusEnum.pendingReview: 'pending_review',
  DisciplineRecordDetailStatusEnum.confirmed: 'confirmed',
  DisciplineRecordDetailStatusEnum.hearing: 'hearing',
  DisciplineRecordDetailStatusEnum.overturned: 'overturned',
  DisciplineRecordDetailStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$DisciplineRecordDetailHearingDecisionEnumEnumMap = {
  DisciplineRecordDetailHearingDecisionEnum.noAction: 'no_action',
  DisciplineRecordDetailHearingDecisionEnum.sanction: 'sanction',
  DisciplineRecordDetailHearingDecisionEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
