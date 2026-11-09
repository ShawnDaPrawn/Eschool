// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'diagnostic_group_topics_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DiagnosticGroupTopicsInner _$DiagnosticGroupTopicsInnerFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'DiagnosticGroupTopicsInner',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'library_topic_id',
        'name',
        'secure',
        'developing',
        'gap',
        'not_reached',
      ],
    );
    final val = DiagnosticGroupTopicsInner(
      libraryTopicId: $checkedConvert(
        'library_topic_id',
        (v) => (v as num).toInt(),
      ),
      name: $checkedConvert('name', (v) => v as String),
      secure: $checkedConvert('secure', (v) => (v as num).toInt()),
      developing: $checkedConvert('developing', (v) => (v as num).toInt()),
      gap: $checkedConvert('gap', (v) => (v as num).toInt()),
      notReached: $checkedConvert('not_reached', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'libraryTopicId': 'library_topic_id',
    'notReached': 'not_reached',
  },
);

Map<String, dynamic> _$DiagnosticGroupTopicsInnerToJson(
  DiagnosticGroupTopicsInner instance,
) => <String, dynamic>{
  'library_topic_id': instance.libraryTopicId,
  'name': instance.name,
  'secure': instance.secure,
  'developing': instance.developing,
  'gap': instance.gap,
  'not_reached': instance.notReached,
};
