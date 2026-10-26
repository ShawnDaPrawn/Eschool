// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'foundation_registration.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FoundationRegistration _$FoundationRegistrationFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'FoundationRegistration',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['id', 'tablet_access_code', 'kind', 'expires_at'],
    );
    final val = FoundationRegistration(
      id: $checkedConvert('id', (v) => v as String),
      tabletAccessCode: $checkedConvert(
        'tablet_access_code',
        (v) => v as String,
      ),
      kind: $checkedConvert('kind', (v) => v as String),
      expiresAt: $checkedConvert(
        'expires_at',
        (v) => DateTime.parse(v as String),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'tabletAccessCode': 'tablet_access_code',
    'expiresAt': 'expires_at',
  },
);

Map<String, dynamic> _$FoundationRegistrationToJson(
  FoundationRegistration instance,
) => <String, dynamic>{
  'id': instance.id,
  'tablet_access_code': instance.tabletAccessCode,
  'kind': instance.kind,
  'expires_at': instance.expiresAt.toIso8601String(),
};
