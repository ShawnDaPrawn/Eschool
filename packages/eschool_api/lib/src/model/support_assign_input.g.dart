// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'support_assign_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SupportAssignInput _$SupportAssignInputFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'SupportAssignInput',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'operation_id',
            'reason',
            'learner_id',
            'user_id',
            'starts_on',
            'ends_on',
          ],
        );
        final val = SupportAssignInput(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          reason: $checkedConvert('reason', (v) => v as String),
          learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
          userId: $checkedConvert('user_id', (v) => (v as num).toInt()),
          startsOn: $checkedConvert('starts_on', (v) => v as String),
          endsOn: $checkedConvert('ends_on', (v) => v as String),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'learnerId': 'learner_id',
        'userId': 'user_id',
        'startsOn': 'starts_on',
        'endsOn': 'ends_on',
      },
    );

Map<String, dynamic> _$SupportAssignInputToJson(SupportAssignInput instance) =>
    <String, dynamic>{
      'operation_id': instance.operationId,
      'reason': instance.reason,
      'learner_id': instance.learnerId,
      'user_id': instance.userId,
      'starts_on': instance.startsOn,
      'ends_on': instance.endsOn,
    };
