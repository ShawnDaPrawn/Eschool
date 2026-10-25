// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'secure_card_credential.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SecureCardCredential _$SecureCardCredentialFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'SecureCardCredential',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'card',
        'credential',
        'expires_at',
        'key_id',
        'public_key',
      ],
    );
    final val = SecureCardCredential(
      card: $checkedConvert(
        'card',
        (v) => SecureCard.fromJson(v as Map<String, dynamic>),
      ),
      credential: $checkedConvert('credential', (v) => v as String),
      expiresAt: $checkedConvert(
        'expires_at',
        (v) => DateTime.parse(v as String),
      ),
      keyId: $checkedConvert('key_id', (v) => v as String),
      publicKey: $checkedConvert('public_key', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'expiresAt': 'expires_at',
    'keyId': 'key_id',
    'publicKey': 'public_key',
  },
);

Map<String, dynamic> _$SecureCardCredentialToJson(
  SecureCardCredential instance,
) => <String, dynamic>{
  'card': instance.card.toJson(),
  'credential': instance.credential,
  'expires_at': instance.expiresAt.toIso8601String(),
  'key_id': instance.keyId,
  'public_key': instance.publicKey,
};
