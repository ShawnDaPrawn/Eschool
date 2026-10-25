// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'intervention_queue_item.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

InterventionQueueItem _$InterventionQueueItemFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'InterventionQueueItem',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'learner_id',
        'owner_id',
        'grade_level_id',
        'review_on',
        'state',
        'revision',
        'can_read_body',
      ],
    );
    final val = InterventionQueueItem(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      ownerId: $checkedConvert('owner_id', (v) => (v as num).toInt()),
      gradeLevelId: $checkedConvert(
        'grade_level_id',
        (v) => (v as num).toInt(),
      ),
      reviewOn: $checkedConvert('review_on', (v) => v as String),
      state: $checkedConvert(
        'state',
        (v) => $enumDecode(
          _$InterventionQueueItemStateEnumEnumMap,
          v,
          unknownValue: InterventionQueueItemStateEnum.unknownDefaultOpenApi,
        ),
      ),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      canReadBody: $checkedConvert('can_read_body', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'ownerId': 'owner_id',
    'gradeLevelId': 'grade_level_id',
    'reviewOn': 'review_on',
    'canReadBody': 'can_read_body',
  },
);

Map<String, dynamic> _$InterventionQueueItemToJson(
  InterventionQueueItem instance,
) => <String, dynamic>{
  'id': instance.id,
  'learner_id': instance.learnerId,
  'owner_id': instance.ownerId,
  'grade_level_id': instance.gradeLevelId,
  'review_on': instance.reviewOn,
  'state': _$InterventionQueueItemStateEnumEnumMap[instance.state]!,
  'revision': instance.revision,
  'can_read_body': instance.canReadBody,
};

const _$InterventionQueueItemStateEnumEnumMap = {
  InterventionQueueItemStateEnum.active: 'active',
  InterventionQueueItemStateEnum.completed: 'completed',
  InterventionQueueItemStateEnum.cancelled: 'cancelled',
  InterventionQueueItemStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
