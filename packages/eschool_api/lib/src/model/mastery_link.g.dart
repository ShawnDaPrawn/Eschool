// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'mastery_link.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

MasteryLink _$MasteryLinkFromJson(Map<String, dynamic> json) => $checkedCreate(
  'MasteryLink',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['topic_id', 'prerequisite_id', 'source'],
    );
    final val = MasteryLink(
      topicId: $checkedConvert('topic_id', (v) => (v as num).toInt()),
      prerequisiteId: $checkedConvert(
        'prerequisite_id',
        (v) => (v as num).toInt(),
      ),
      source_: $checkedConvert('source', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'topicId': 'topic_id',
    'prerequisiteId': 'prerequisite_id',
    'source_': 'source',
  },
);

Map<String, dynamic> _$MasteryLinkToJson(MasteryLink instance) =>
    <String, dynamic>{
      'topic_id': instance.topicId,
      'prerequisite_id': instance.prerequisiteId,
      'source': instance.source_,
    };
