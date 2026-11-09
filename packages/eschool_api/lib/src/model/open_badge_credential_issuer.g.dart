// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'open_badge_credential_issuer.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

OpenBadgeCredentialIssuer _$OpenBadgeCredentialIssuerFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('OpenBadgeCredentialIssuer', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'type', 'name']);
  final val = OpenBadgeCredentialIssuer(
    id: $checkedConvert('id', (v) => v as String),
    type: $checkedConvert(
      'type',
      (v) => (v as List<dynamic>).map((e) => e as String).toList(),
    ),
    name: $checkedConvert('name', (v) => v as String),
  );
  return val;
});

Map<String, dynamic> _$OpenBadgeCredentialIssuerToJson(
  OpenBadgeCredentialIssuer instance,
) => <String, dynamic>{
  'id': instance.id,
  'type': instance.type,
  'name': instance.name,
};
