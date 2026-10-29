// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'commerce_cancel_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommerceCancelWrite _$CommerceCancelWriteFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'CommerceCancelWrite',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const ['operation_id', 'private_reason', 'confirmed'],
        );
        final val = CommerceCancelWrite(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          privateReason: $checkedConvert('private_reason', (v) => v as String),
          confirmed: $checkedConvert('confirmed', (v) => v as bool),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'privateReason': 'private_reason',
      },
    );

Map<String, dynamic> _$CommerceCancelWriteToJson(
  CommerceCancelWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'private_reason': instance.privateReason,
  'confirmed': instance.confirmed,
};
