// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'secure_encoding_context.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SecureEncodingContext _$SecureEncodingContextFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'SecureEncodingContext',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school_id',
        'device_id',
        'plan_id',
        'card_id',
        'revision',
        'issued_at',
        'expires_at',
      ],
    );
    final val = SecureEncodingContext(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      deviceId: $checkedConvert('device_id', (v) => v as String),
      planId: $checkedConvert('plan_id', (v) => v as String),
      cardId: $checkedConvert('card_id', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      issuedAt: $checkedConvert('issued_at', (v) => (v as num).toInt()),
      expiresAt: $checkedConvert('expires_at', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'deviceId': 'device_id',
    'planId': 'plan_id',
    'cardId': 'card_id',
    'issuedAt': 'issued_at',
    'expiresAt': 'expires_at',
  },
);

Map<String, dynamic> _$SecureEncodingContextToJson(
  SecureEncodingContext instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'device_id': instance.deviceId,
  'plan_id': instance.planId,
  'card_id': instance.cardId,
  'revision': instance.revision,
  'issued_at': instance.issuedAt,
  'expires_at': instance.expiresAt,
};
