// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_consent_signed.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyConsentSigned _$FamilyConsentSignedFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'FamilyConsentSigned',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'request_id',
            'learner_id',
            'decision',
            'revision',
            'signed_at',
          ],
        );
        final val = FamilyConsentSigned(
          id: $checkedConvert('id', (v) => v as String),
          requestId: $checkedConvert('request_id', (v) => v as String),
          learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
          decision: $checkedConvert('decision', (v) => v as String),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
          signedAt: $checkedConvert(
            'signed_at',
            (v) => DateTime.parse(v as String),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'requestId': 'request_id',
        'learnerId': 'learner_id',
        'signedAt': 'signed_at',
      },
    );

Map<String, dynamic> _$FamilyConsentSignedToJson(
  FamilyConsentSigned instance,
) => <String, dynamic>{
  'id': instance.id,
  'request_id': instance.requestId,
  'learner_id': instance.learnerId,
  'decision': instance.decision,
  'revision': instance.revision,
  'signed_at': instance.signedAt.toIso8601String(),
};
