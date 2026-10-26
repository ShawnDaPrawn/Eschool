// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_consent_cancelled.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyConsentCancelled _$FamilyConsentCancelledFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('FamilyConsentCancelled', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'status', 'revision']);
  final val = FamilyConsentCancelled(
    id: $checkedConvert('id', (v) => v as String),
    status: $checkedConvert('status', (v) => v as String),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$FamilyConsentCancelledToJson(
  FamilyConsentCancelled instance,
) => <String, dynamic>{
  'id': instance.id,
  'status': instance.status,
  'revision': instance.revision,
};
