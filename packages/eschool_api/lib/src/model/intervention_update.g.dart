// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'intervention_update.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

InterventionUpdate _$InterventionUpdateFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'InterventionUpdate',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'operation_id',
            'expected_revision',
            'action',
            'review_on',
            'state',
          ],
        );
        final val = InterventionUpdate(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          expectedRevision: $checkedConvert(
            'expected_revision',
            (v) => (v as num).toInt(),
          ),
          action: $checkedConvert('action', (v) => v as String),
          reviewOn: $checkedConvert('review_on', (v) => v as String),
          state: $checkedConvert(
            'state',
            (v) => $enumDecode(
              _$InterventionUpdateStateEnumEnumMap,
              v,
              unknownValue: InterventionUpdateStateEnum.unknownDefaultOpenApi,
            ),
          ),
          outcome: $checkedConvert('outcome', (v) => v as String?),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'expectedRevision': 'expected_revision',
        'reviewOn': 'review_on',
      },
    );

Map<String, dynamic> _$InterventionUpdateToJson(InterventionUpdate instance) =>
    <String, dynamic>{
      'operation_id': instance.operationId,
      'expected_revision': instance.expectedRevision,
      'action': instance.action,
      'review_on': instance.reviewOn,
      'state': _$InterventionUpdateStateEnumEnumMap[instance.state]!,
      'outcome': ?instance.outcome,
    };

const _$InterventionUpdateStateEnumEnumMap = {
  InterventionUpdateStateEnum.active: 'active',
  InterventionUpdateStateEnum.completed: 'completed',
  InterventionUpdateStateEnum.cancelled: 'cancelled',
  InterventionUpdateStateEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
