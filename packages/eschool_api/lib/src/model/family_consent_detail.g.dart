// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_consent_detail.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyConsentDetail _$FamilyConsentDetailFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'FamilyConsentDetail',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'title',
            'status',
            'event_starts_at',
            'event_ends_at',
            'respond_by',
            'learner_id',
            'wording',
            'fingerprint',
            'revision',
            'decision',
            'signed_at',
          ],
        );
        final val = FamilyConsentDetail(
          id: $checkedConvert('id', (v) => v as String),
          title: $checkedConvert('title', (v) => v as String),
          status: $checkedConvert('status', (v) => v as String),
          eventStartsAt: $checkedConvert(
            'event_starts_at',
            (v) => DateTime.parse(v as String),
          ),
          eventEndsAt: $checkedConvert(
            'event_ends_at',
            (v) => DateTime.parse(v as String),
          ),
          respondBy: $checkedConvert(
            'respond_by',
            (v) => DateTime.parse(v as String),
          ),
          learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
          wording: $checkedConvert('wording', (v) => v as String),
          fingerprint: $checkedConvert('fingerprint', (v) => v as String),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
          decision: $checkedConvert('decision', (v) => v as String?),
          signedAt: $checkedConvert(
            'signed_at',
            (v) => v == null ? null : DateTime.parse(v as String),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'eventStartsAt': 'event_starts_at',
        'eventEndsAt': 'event_ends_at',
        'respondBy': 'respond_by',
        'learnerId': 'learner_id',
        'signedAt': 'signed_at',
      },
    );

Map<String, dynamic> _$FamilyConsentDetailToJson(
  FamilyConsentDetail instance,
) => <String, dynamic>{
  'id': instance.id,
  'title': instance.title,
  'status': instance.status,
  'event_starts_at': instance.eventStartsAt.toIso8601String(),
  'event_ends_at': instance.eventEndsAt.toIso8601String(),
  'respond_by': instance.respondBy.toIso8601String(),
  'learner_id': instance.learnerId,
  'wording': instance.wording,
  'fingerprint': instance.fingerprint,
  'revision': instance.revision,
  'decision': instance.decision,
  'signed_at': instance.signedAt?.toIso8601String(),
};
