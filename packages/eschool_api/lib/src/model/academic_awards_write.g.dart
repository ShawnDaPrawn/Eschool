// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_awards_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicAwardsWrite _$AcademicAwardsWriteFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'AcademicAwardsWrite',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'operation_id',
            'award_definition_id',
            'basis',
            'learner_ids',
            'presented_on',
          ],
        );
        final val = AcademicAwardsWrite(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          awardDefinitionId: $checkedConvert(
            'award_definition_id',
            (v) => (v as num).toInt(),
          ),
          basis: $checkedConvert(
            'basis',
            (v) => $enumDecode(
              _$AcademicAwardsWriteBasisEnumEnumMap,
              v,
              unknownValue: AcademicAwardsWriteBasisEnum.unknownDefaultOpenApi,
            ),
          ),
          learnerIds: $checkedConvert(
            'learner_ids',
            (v) => (v as List<dynamic>).map((e) => (e as num).toInt()).toList(),
          ),
          presentedOn: $checkedConvert('presented_on', (v) => v as String),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'awardDefinitionId': 'award_definition_id',
        'learnerIds': 'learner_ids',
        'presentedOn': 'presented_on',
      },
    );

Map<String, dynamic> _$AcademicAwardsWriteToJson(
  AcademicAwardsWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'award_definition_id': instance.awardDefinitionId,
  'basis': _$AcademicAwardsWriteBasisEnumEnumMap[instance.basis]!,
  'learner_ids': instance.learnerIds,
  'presented_on': instance.presentedOn,
};

const _$AcademicAwardsWriteBasisEnumEnumMap = {
  AcademicAwardsWriteBasisEnum.participation: 'participation',
  AcademicAwardsWriteBasisEnum.placing: 'placing',
  AcademicAwardsWriteBasisEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
