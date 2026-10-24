// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_transition_record.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicTransitionRecord _$AcademicTransitionRecordFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicTransitionRecord',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'learner_id',
        'session_year_id',
        'grade_level_id',
        'kind',
        'effective_on',
        'reason',
        'operation_id',
        'result',
        'created_at',
      ],
    );
    final val = AcademicTransitionRecord(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      gradeLevelId: $checkedConvert(
        'grade_level_id',
        (v) => (v as num?)?.toInt(),
      ),
      kind: $checkedConvert('kind', (v) => v as String),
      effectiveOn: $checkedConvert('effective_on', (v) => v as String),
      reason: $checkedConvert('reason', (v) => v as String),
      operationId: $checkedConvert('operation_id', (v) => v as String),
      result: $checkedConvert(
        'result',
        (v) => AcademicTransitionResult.fromJson(v as Map<String, dynamic>),
      ),
      createdAt: $checkedConvert(
        'created_at',
        (v) => DateTime.parse(v as String),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'sessionYearId': 'session_year_id',
    'gradeLevelId': 'grade_level_id',
    'effectiveOn': 'effective_on',
    'operationId': 'operation_id',
    'createdAt': 'created_at',
  },
);

Map<String, dynamic> _$AcademicTransitionRecordToJson(
  AcademicTransitionRecord instance,
) => <String, dynamic>{
  'id': instance.id,
  'learner_id': instance.learnerId,
  'session_year_id': instance.sessionYearId,
  'grade_level_id': instance.gradeLevelId,
  'kind': instance.kind,
  'effective_on': instance.effectiveOn,
  'reason': instance.reason,
  'operation_id': instance.operationId,
  'result': instance.result.toJson(),
  'created_at': instance.createdAt.toIso8601String(),
};
