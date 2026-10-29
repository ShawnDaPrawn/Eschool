// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'admission_transfer_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AdmissionTransferWrite _$AdmissionTransferWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AdmissionTransferWrite',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'revision',
        'receive_code',
        'recipient_id',
        'confirmed',
      ],
    );
    final val = AdmissionTransferWrite(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      receiveCode: $checkedConvert('receive_code', (v) => v as String),
      recipientId: $checkedConvert('recipient_id', (v) => (v as num).toInt()),
      confirmed: $checkedConvert('confirmed', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'receiveCode': 'receive_code',
    'recipientId': 'recipient_id',
  },
);

Map<String, dynamic> _$AdmissionTransferWriteToJson(
  AdmissionTransferWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'revision': instance.revision,
  'receive_code': instance.receiveCode,
  'recipient_id': instance.recipientId,
  'confirmed': instance.confirmed,
};
