// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_consent_create.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyConsentCreate _$FamilyConsentCreateFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'FamilyConsentCreate',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'operation_id',
            'activity_group_id',
            'title',
            'wording',
            'event_starts_at',
            'event_ends_at',
            'respond_by',
            'learner_ids',
          ],
        );
        final val = FamilyConsentCreate(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          activityGroupId: $checkedConvert(
            'activity_group_id',
            (v) => (v as num).toInt(),
          ),
          title: $checkedConvert('title', (v) => v as String),
          wording: $checkedConvert('wording', (v) => v as String),
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
          learnerIds: $checkedConvert(
            'learner_ids',
            (v) => (v as List<dynamic>).map((e) => (e as num).toInt()).toSet(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'activityGroupId': 'activity_group_id',
        'eventStartsAt': 'event_starts_at',
        'eventEndsAt': 'event_ends_at',
        'respondBy': 'respond_by',
        'learnerIds': 'learner_ids',
      },
    );

Map<String, dynamic> _$FamilyConsentCreateToJson(
  FamilyConsentCreate instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'activity_group_id': instance.activityGroupId,
  'title': instance.title,
  'wording': instance.wording,
  'event_starts_at': instance.eventStartsAt.toIso8601String(),
  'event_ends_at': instance.eventEndsAt.toIso8601String(),
  'respond_by': instance.respondBy.toIso8601String(),
  'learner_ids': instance.learnerIds.toList(),
};
