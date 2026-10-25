// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'mastery_priority.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

MasteryPriority _$MasteryPriorityFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('MasteryPriority', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const [
      'topic_id',
      'name',
      'level',
      'confidence_bp',
      'reason',
      'path',
    ],
  );
  final val = MasteryPriority(
    topicId: $checkedConvert('topic_id', (v) => (v as num).toInt()),
    name: $checkedConvert('name', (v) => v as String),
    level: $checkedConvert('level', (v) => (v as num?)?.toInt()),
    confidenceBp: $checkedConvert('confidence_bp', (v) => (v as num).toInt()),
    reason: $checkedConvert(
      'reason',
      (v) => $enumDecode(
        _$MasteryPriorityReasonEnumEnumMap,
        v,
        unknownValue: MasteryPriorityReasonEnum.unknownDefaultOpenApi,
      ),
    ),
    path: $checkedConvert(
      'path',
      (v) => (v as List<dynamic>).map((e) => (e as num).toInt()).toList(),
    ),
  );
  return val;
}, fieldKeyMap: const {'topicId': 'topic_id', 'confidenceBp': 'confidence_bp'});

Map<String, dynamic> _$MasteryPriorityToJson(MasteryPriority instance) =>
    <String, dynamic>{
      'topic_id': instance.topicId,
      'name': instance.name,
      'level': instance.level,
      'confidence_bp': instance.confidenceBp,
      'reason': _$MasteryPriorityReasonEnumEnumMap[instance.reason]!,
      'path': instance.path,
    };

const _$MasteryPriorityReasonEnumEnumMap = {
  MasteryPriorityReasonEnum.notYetAssessed: 'not_yet_assessed',
  MasteryPriorityReasonEnum.prerequisiteGap: 'prerequisite_gap',
  MasteryPriorityReasonEnum.topicGap: 'topic_gap',
  MasteryPriorityReasonEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
