// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_consent_managed_recipient.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyConsentManagedRecipient _$FamilyConsentManagedRecipientFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('FamilyConsentManagedRecipient', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const [
      'learner_id',
      'name',
      'current',
      'revision',
      'decision',
      'signed_at',
      'effective',
    ],
  );
  final val = FamilyConsentManagedRecipient(
    learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
    name: $checkedConvert('name', (v) => v as String),
    current: $checkedConvert('current', (v) => v as bool),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    decision: $checkedConvert('decision', (v) => v as String?),
    signedAt: $checkedConvert(
      'signed_at',
      (v) => v == null ? null : DateTime.parse(v as String),
    ),
    effective: $checkedConvert('effective', (v) => v as bool),
  );
  return val;
}, fieldKeyMap: const {'learnerId': 'learner_id', 'signedAt': 'signed_at'});

Map<String, dynamic> _$FamilyConsentManagedRecipientToJson(
  FamilyConsentManagedRecipient instance,
) => <String, dynamic>{
  'learner_id': instance.learnerId,
  'name': instance.name,
  'current': instance.current,
  'revision': instance.revision,
  'decision': instance.decision,
  'signed_at': instance.signedAt?.toIso8601String(),
  'effective': instance.effective,
};
