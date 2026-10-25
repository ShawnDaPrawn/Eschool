// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'intervention_owner_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

InterventionOwnerWrite _$InterventionOwnerWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'InterventionOwnerWrite',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'expected_revision',
        'owner_id',
        'reason',
      ],
    );
    final val = InterventionOwnerWrite(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      expectedRevision: $checkedConvert(
        'expected_revision',
        (v) => (v as num).toInt(),
      ),
      ownerId: $checkedConvert('owner_id', (v) => (v as num).toInt()),
      reason: $checkedConvert('reason', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'expectedRevision': 'expected_revision',
    'ownerId': 'owner_id',
  },
);

Map<String, dynamic> _$InterventionOwnerWriteToJson(
  InterventionOwnerWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'expected_revision': instance.expectedRevision,
  'owner_id': instance.ownerId,
  'reason': instance.reason,
};
