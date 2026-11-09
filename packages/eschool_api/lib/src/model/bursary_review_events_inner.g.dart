// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'bursary_review_events_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

BursaryReviewEventsInner _$BursaryReviewEventsInnerFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'BursaryReviewEventsInner',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'action',
        'actor_id',
        'actor_name',
        'reasons',
        'award_kind',
        'award_value',
        'at',
      ],
    );
    final val = BursaryReviewEventsInner(
      action: $checkedConvert('action', (v) => v as String),
      actorId: $checkedConvert('actor_id', (v) => (v as num).toInt()),
      actorName: $checkedConvert('actor_name', (v) => v as String),
      reasons: $checkedConvert('reasons', (v) => v as String?),
      awardKind: $checkedConvert('award_kind', (v) => v as String?),
      awardValue: $checkedConvert('award_value', (v) => (v as num?)?.toInt()),
      at: $checkedConvert('at', (v) => DateTime.parse(v as String)),
    );
    return val;
  },
  fieldKeyMap: const {
    'actorId': 'actor_id',
    'actorName': 'actor_name',
    'awardKind': 'award_kind',
    'awardValue': 'award_value',
  },
);

Map<String, dynamic> _$BursaryReviewEventsInnerToJson(
  BursaryReviewEventsInner instance,
) => <String, dynamic>{
  'action': instance.action,
  'actor_id': instance.actorId,
  'actor_name': instance.actorName,
  'reasons': instance.reasons,
  'award_kind': instance.awardKind,
  'award_value': instance.awardValue,
  'at': instance.at.toIso8601String(),
};
