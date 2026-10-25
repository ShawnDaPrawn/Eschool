// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'my_grade_pending_record.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

MyGradePendingRecord _$MyGradePendingRecordFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'MyGradePendingRecord',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'learner_id',
        'revision',
        'occurred_on',
        'status',
        'learner_name',
      ],
    );
    final val = MyGradePendingRecord(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      occurredOn: $checkedConvert('occurred_on', (v) => v as String),
      status: $checkedConvert(
        'status',
        (v) => $enumDecode(
          _$MyGradePendingRecordStatusEnumEnumMap,
          v,
          unknownValue: MyGradePendingRecordStatusEnum.unknownDefaultOpenApi,
        ),
      ),
      learnerName: $checkedConvert('learner_name', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'occurredOn': 'occurred_on',
    'learnerName': 'learner_name',
  },
);

Map<String, dynamic> _$MyGradePendingRecordToJson(
  MyGradePendingRecord instance,
) => <String, dynamic>{
  'id': instance.id,
  'learner_id': instance.learnerId,
  'revision': instance.revision,
  'occurred_on': instance.occurredOn,
  'status': _$MyGradePendingRecordStatusEnumEnumMap[instance.status]!,
  'learner_name': instance.learnerName,
};

const _$MyGradePendingRecordStatusEnumEnumMap = {
  MyGradePendingRecordStatusEnum.pendingReview: 'pending_review',
  MyGradePendingRecordStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
