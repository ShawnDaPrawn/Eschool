// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'support_disclose_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SupportDiscloseInput _$SupportDiscloseInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'SupportDiscloseInput',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'revision',
        'reason',
        'user_id',
        'expires_on',
        'accommodations',
        'confirmation',
      ],
    );
    final val = SupportDiscloseInput(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      reason: $checkedConvert('reason', (v) => v as String),
      userId: $checkedConvert('user_id', (v) => (v as num).toInt()),
      expiresOn: $checkedConvert('expires_on', (v) => v as String),
      accommodations: $checkedConvert('accommodations', (v) => v as String),
      confirmation: $checkedConvert('confirmation', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'userId': 'user_id',
    'expiresOn': 'expires_on',
  },
);

Map<String, dynamic> _$SupportDiscloseInputToJson(
  SupportDiscloseInput instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'revision': instance.revision,
  'reason': instance.reason,
  'user_id': instance.userId,
  'expires_on': instance.expiresOn,
  'accommodations': instance.accommodations,
  'confirmation': instance.confirmation,
};
