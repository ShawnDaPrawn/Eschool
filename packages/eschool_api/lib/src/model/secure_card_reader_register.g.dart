// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'secure_card_reader_register.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SecureCardReaderRegister _$SecureCardReaderRegisterFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'SecureCardReaderRegister',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'name',
        'kind',
        'public_key',
        'days',
      ],
    );
    final val = SecureCardReaderRegister(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      name: $checkedConvert('name', (v) => v as String),
      kind: $checkedConvert('kind', (v) => v as String),
      publicKey: $checkedConvert('public_key', (v) => v as String),
      days: $checkedConvert('days', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {'operationId': 'operation_id', 'publicKey': 'public_key'},
);

Map<String, dynamic> _$SecureCardReaderRegisterToJson(
  SecureCardReaderRegister instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'name': instance.name,
  'kind': instance.kind,
  'public_key': instance.publicKey,
  'days': instance.days,
};
