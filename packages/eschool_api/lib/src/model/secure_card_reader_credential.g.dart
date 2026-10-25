// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'secure_card_reader_credential.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SecureCardReaderCredential _$SecureCardReaderCredentialFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SecureCardReaderCredential', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['id', 'credential', 'kind', 'expires_at'],
  );
  final val = SecureCardReaderCredential(
    id: $checkedConvert('id', (v) => v as String),
    credential: $checkedConvert('credential', (v) => v as String),
    kind: $checkedConvert('kind', (v) => v as String),
    expiresAt: $checkedConvert(
      'expires_at',
      (v) => DateTime.parse(v as String),
    ),
  );
  return val;
}, fieldKeyMap: const {'expiresAt': 'expires_at'});

Map<String, dynamic> _$SecureCardReaderCredentialToJson(
  SecureCardReaderCredential instance,
) => <String, dynamic>{
  'id': instance.id,
  'credential': instance.credential,
  'kind': instance.kind,
  'expires_at': instance.expiresAt.toIso8601String(),
};
