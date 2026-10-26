// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'secure_encoding_confirmation.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SecureEncodingConfirmation _$SecureEncodingConfirmationFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SecureEncodingConfirmation', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['operation_id', 'picc', 'mac']);
  final val = SecureEncodingConfirmation(
    operationId: $checkedConvert('operation_id', (v) => v as String),
    picc: $checkedConvert('picc', (v) => v as String),
    mac: $checkedConvert('mac', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$SecureEncodingConfirmationToJson(
  SecureEncodingConfirmation instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'picc': instance.picc,
  'mac': instance.mac,
};
