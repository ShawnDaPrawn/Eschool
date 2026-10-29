// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'admission_redeem_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AdmissionRedeemWrite _$AdmissionRedeemWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AdmissionRedeemWrite',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['operation_id', 'revision', 'confirmed'],
    );
    final val = AdmissionRedeemWrite(
      qrToken: $checkedConvert('qr_token', (v) => v as String?),
      ticketNumber: $checkedConvert('ticket_number', (v) => v as String?),
      operationId: $checkedConvert('operation_id', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      confirmed: $checkedConvert('confirmed', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'qrToken': 'qr_token',
    'ticketNumber': 'ticket_number',
    'operationId': 'operation_id',
  },
);

Map<String, dynamic> _$AdmissionRedeemWriteToJson(
  AdmissionRedeemWrite instance,
) => <String, dynamic>{
  'qr_token': ?instance.qrToken,
  'ticket_number': ?instance.ticketNumber,
  'operation_id': instance.operationId,
  'revision': instance.revision,
  'confirmed': instance.confirmed,
};
