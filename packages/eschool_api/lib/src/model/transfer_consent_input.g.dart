// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'transfer_consent_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TransferConsentInput _$TransferConsentInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TransferConsentInput', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const [
      'operation_id',
      'revision',
      'fingerprint',
      'version',
      'confirmation',
    ],
  );
  final val = TransferConsentInput(
    operationId: $checkedConvert('operation_id', (v) => v as String),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    fingerprint: $checkedConvert('fingerprint', (v) => v as String),
    version: $checkedConvert('version', (v) => v as String),
    confirmation: $checkedConvert('confirmation', (v) => v as bool),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$TransferConsentInputToJson(
  TransferConsentInput instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'revision': instance.revision,
  'fingerprint': instance.fingerprint,
  'version': instance.version,
  'confirmation': instance.confirmation,
};
