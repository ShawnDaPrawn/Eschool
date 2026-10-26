// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_consent_created.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyConsentCreated _$FamilyConsentCreatedFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('FamilyConsentCreated', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'fingerprint', 'revision']);
  final val = FamilyConsentCreated(
    id: $checkedConvert('id', (v) => v as String),
    fingerprint: $checkedConvert('fingerprint', (v) => v as String),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$FamilyConsentCreatedToJson(
  FamilyConsentCreated instance,
) => <String, dynamic>{
  'id': instance.id,
  'fingerprint': instance.fingerprint,
  'revision': instance.revision,
};
