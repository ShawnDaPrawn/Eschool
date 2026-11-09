// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'open_badge_credential.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

OpenBadgeCredential _$OpenBadgeCredentialFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('OpenBadgeCredential', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const [
      '@context',
      'id',
      'type',
      'issuer',
      'validFrom',
      'name',
      'credentialSubject',
      'credentialStatus',
      'proof',
    ],
  );
  final val = OpenBadgeCredential(
    atContext: $checkedConvert(
      '@context',
      (v) => (v as List<dynamic>).map((e) => e as String).toList(),
    ),
    id: $checkedConvert('id', (v) => v as String),
    type: $checkedConvert(
      'type',
      (v) => (v as List<dynamic>).map((e) => e as String).toList(),
    ),
    issuer: $checkedConvert(
      'issuer',
      (v) => OpenBadgeCredentialIssuer.fromJson(v as Map<String, dynamic>),
    ),
    validFrom: $checkedConvert('validFrom', (v) => v as String),
    name: $checkedConvert('name', (v) => v as String),
    awardedDate: $checkedConvert('awardedDate', (v) => v as String?),
    credentialSubject: $checkedConvert(
      'credentialSubject',
      (v) =>
          (v as Map<String, dynamic>).map((k, e) => MapEntry(k, e as Object)),
    ),
    credentialStatus: $checkedConvert(
      'credentialStatus',
      (v) =>
          (v as Map<String, dynamic>).map((k, e) => MapEntry(k, e as Object)),
    ),
    proof: $checkedConvert(
      'proof',
      (v) => OpenBadgeCredentialProof.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
}, fieldKeyMap: const {'atContext': '@context'});

Map<String, dynamic> _$OpenBadgeCredentialToJson(
  OpenBadgeCredential instance,
) => <String, dynamic>{
  '@context': instance.atContext,
  'id': instance.id,
  'type': instance.type,
  'issuer': instance.issuer.toJson(),
  'validFrom': instance.validFrom,
  'name': instance.name,
  'awardedDate': ?instance.awardedDate,
  'credentialSubject': instance.credentialSubject,
  'credentialStatus': instance.credentialStatus,
  'proof': instance.proof.toJson(),
};
