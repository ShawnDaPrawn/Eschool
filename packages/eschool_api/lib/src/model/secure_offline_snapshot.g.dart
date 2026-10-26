// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'secure_offline_snapshot.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SecureOfflineSnapshot _$SecureOfflineSnapshotFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'SecureOfflineSnapshot',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'request_id',
        'envelope',
        'signing_key_id',
        'signing_public_key',
        'next_after',
      ],
    );
    final val = SecureOfflineSnapshot(
      requestId: $checkedConvert('request_id', (v) => v as String),
      envelope: $checkedConvert(
        'envelope',
        (v) => SecureOfflineEnvelope.fromJson(v as Map<String, dynamic>),
      ),
      signingKeyId: $checkedConvert('signing_key_id', (v) => v as String),
      signingPublicKey: $checkedConvert(
        'signing_public_key',
        (v) => v as String,
      ),
      nextAfter: $checkedConvert('next_after', (v) => v as String?),
    );
    return val;
  },
  fieldKeyMap: const {
    'requestId': 'request_id',
    'signingKeyId': 'signing_key_id',
    'signingPublicKey': 'signing_public_key',
    'nextAfter': 'next_after',
  },
);

Map<String, dynamic> _$SecureOfflineSnapshotToJson(
  SecureOfflineSnapshot instance,
) => <String, dynamic>{
  'request_id': instance.requestId,
  'envelope': instance.envelope.toJson(),
  'signing_key_id': instance.signingKeyId,
  'signing_public_key': instance.signingPublicKey,
  'next_after': instance.nextAfter,
};
