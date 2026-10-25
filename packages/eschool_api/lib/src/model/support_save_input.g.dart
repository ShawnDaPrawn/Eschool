// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'support_save_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SupportSaveInput _$SupportSaveInputFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'SupportSaveInput',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'operation_id',
            'learner_id',
            'started_on',
            'sias_stage',
            'needs',
            'accommodations',
          ],
        );
        final val = SupportSaveInput(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
          revision: $checkedConvert('revision', (v) => (v as num?)?.toInt()),
          startedOn: $checkedConvert('started_on', (v) => v as String),
          siasStage: $checkedConvert('sias_stage', (v) => v as String),
          needs: $checkedConvert('needs', (v) => v as String),
          accommodations: $checkedConvert('accommodations', (v) => v as String),
          reviewDueOn: $checkedConvert('review_due_on', (v) => v as String?),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'learnerId': 'learner_id',
        'startedOn': 'started_on',
        'siasStage': 'sias_stage',
        'reviewDueOn': 'review_due_on',
      },
    );

Map<String, dynamic> _$SupportSaveInputToJson(SupportSaveInput instance) =>
    <String, dynamic>{
      'operation_id': instance.operationId,
      'learner_id': instance.learnerId,
      'revision': ?instance.revision,
      'started_on': instance.startedOn,
      'sias_stage': instance.siasStage,
      'needs': instance.needs,
      'accommodations': instance.accommodations,
      'review_due_on': ?instance.reviewDueOn,
    };
