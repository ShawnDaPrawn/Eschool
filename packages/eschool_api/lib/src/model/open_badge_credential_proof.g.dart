// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'open_badge_credential_proof.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

OpenBadgeCredentialProof _$OpenBadgeCredentialProofFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('OpenBadgeCredentialProof', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const [
      'type',
      'cryptosuite',
      'created',
      'verificationMethod',
      'proofPurpose',
      'proofValue',
    ],
  );
  final val = OpenBadgeCredentialProof(
    type: $checkedConvert('type', (v) => v as String),
    cryptosuite: $checkedConvert('cryptosuite', (v) => v as String),
    created: $checkedConvert('created', (v) => v as String),
    verificationMethod: $checkedConvert(
      'verificationMethod',
      (v) => v as String,
    ),
    proofPurpose: $checkedConvert('proofPurpose', (v) => v as String),
    proofValue: $checkedConvert('proofValue', (v) => v as String),
  );
  return val;
});

Map<String, dynamic> _$OpenBadgeCredentialProofToJson(
  OpenBadgeCredentialProof instance,
) => <String, dynamic>{
  'type': instance.type,
  'cryptosuite': instance.cryptosuite,
  'created': instance.created,
  'verificationMethod': instance.verificationMethod,
  'proofPurpose': instance.proofPurpose,
  'proofValue': instance.proofValue,
};
