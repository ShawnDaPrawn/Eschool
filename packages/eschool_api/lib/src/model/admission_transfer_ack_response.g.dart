// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'admission_transfer_ack_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AdmissionTransferAckResponse _$AdmissionTransferAckResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AdmissionTransferAckResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = AdmissionTransferAckResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => AdmissionTransferAck.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$AdmissionTransferAckResponseToJson(
  AdmissionTransferAckResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
