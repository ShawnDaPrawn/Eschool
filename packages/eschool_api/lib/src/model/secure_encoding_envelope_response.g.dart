// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'secure_encoding_envelope_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SecureEncodingEnvelopeResponse _$SecureEncodingEnvelopeResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SecureEncodingEnvelopeResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = SecureEncodingEnvelopeResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => SecureEncodingEnvelope.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$SecureEncodingEnvelopeResponseToJson(
  SecureEncodingEnvelopeResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
