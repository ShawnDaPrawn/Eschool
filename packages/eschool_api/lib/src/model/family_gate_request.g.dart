// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_gate_request.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyGateRequest _$FamilyGateRequestFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'FamilyGateRequest',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'operation_id',
            'learner_id',
            'collector',
            'reason',
            'starts_at',
            'ends_at',
          ],
        );
        final val = FamilyGateRequest(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
          collector: $checkedConvert('collector', (v) => v as String),
          reason: $checkedConvert('reason', (v) => v as String),
          startsAt: $checkedConvert('starts_at', (v) => v as String),
          endsAt: $checkedConvert('ends_at', (v) => v as String),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'learnerId': 'learner_id',
        'startsAt': 'starts_at',
        'endsAt': 'ends_at',
      },
    );

Map<String, dynamic> _$FamilyGateRequestToJson(FamilyGateRequest instance) =>
    <String, dynamic>{
      'operation_id': instance.operationId,
      'learner_id': instance.learnerId,
      'collector': instance.collector,
      'reason': instance.reason,
      'starts_at': instance.startsAt,
      'ends_at': instance.endsAt,
    };
