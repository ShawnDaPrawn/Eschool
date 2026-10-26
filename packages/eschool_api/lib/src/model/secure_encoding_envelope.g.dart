// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'secure_encoding_envelope.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SecureEncodingEnvelope _$SecureEncodingEnvelopeFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SecureEncodingEnvelope', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const [
      'suite',
      'context',
      'ephemeral_public_key',
      'salt',
      'nonce',
      'tag',
      'ciphertext',
    ],
  );
  final val = SecureEncodingEnvelope(
    suite: $checkedConvert('suite', (v) => v as String),
    context: $checkedConvert(
      'context',
      (v) => SecureEncodingContext.fromJson(v as Map<String, dynamic>),
    ),
    ephemeralPublicKey: $checkedConvert(
      'ephemeral_public_key',
      (v) => v as String,
    ),
    salt: $checkedConvert('salt', (v) => v as String),
    nonce: $checkedConvert('nonce', (v) => v as String),
    tag: $checkedConvert('tag', (v) => v as String),
    ciphertext: $checkedConvert('ciphertext', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'ephemeralPublicKey': 'ephemeral_public_key'});

Map<String, dynamic> _$SecureEncodingEnvelopeToJson(
  SecureEncodingEnvelope instance,
) => <String, dynamic>{
  'suite': instance.suite,
  'context': instance.context.toJson(),
  'ephemeral_public_key': instance.ephemeralPublicKey,
  'salt': instance.salt,
  'nonce': instance.nonce,
  'tag': instance.tag,
  'ciphertext': instance.ciphertext,
};
