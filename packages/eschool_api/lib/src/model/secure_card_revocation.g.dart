// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'secure_card_revocation.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SecureCardRevocation _$SecureCardRevocationFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SecureCardRevocation', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['revoked']);
  final val = SecureCardRevocation(
    revoked: $checkedConvert('revoked', (v) => v as bool),
  );
  return val;
});

Map<String, dynamic> _$SecureCardRevocationToJson(
  SecureCardRevocation instance,
) => <String, dynamic>{'revoked': instance.revoked};
