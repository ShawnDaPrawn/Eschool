// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'admission_receive_code_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AdmissionReceiveCodeResponse _$AdmissionReceiveCodeResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AdmissionReceiveCodeResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = AdmissionReceiveCodeResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => AdmissionReceiveCode.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$AdmissionReceiveCodeResponseToJson(
  AdmissionReceiveCodeResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
