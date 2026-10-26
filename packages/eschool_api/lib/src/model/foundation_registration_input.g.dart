// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'foundation_registration_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FoundationRegistrationInput _$FoundationRegistrationInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'FoundationRegistrationInput',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['operation_id', 'name', 'setup_code', 'days'],
    );
    final val = FoundationRegistrationInput(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      name: $checkedConvert('name', (v) => v as String),
      setupCode: $checkedConvert('setup_code', (v) => v as String),
      days: $checkedConvert('days', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {'operationId': 'operation_id', 'setupCode': 'setup_code'},
);

Map<String, dynamic> _$FoundationRegistrationInputToJson(
  FoundationRegistrationInput instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'name': instance.name,
  'setup_code': instance.setupCode,
  'days': instance.days,
};
