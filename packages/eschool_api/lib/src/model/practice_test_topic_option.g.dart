// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_test_topic_option.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeTestTopicOption _$PracticeTestTopicOptionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('PracticeTestTopicOption', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'name', 'source', 'available']);
  final val = PracticeTestTopicOption(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    name: $checkedConvert('name', (v) => v as String),
    source_: $checkedConvert(
      'source',
      (v) => $enumDecode(
        _$PracticeTestTopicOptionSource_EnumEnumMap,
        v,
        unknownValue: PracticeTestTopicOptionSource_Enum.unknownDefaultOpenApi,
      ),
    ),
    available: $checkedConvert('available', (v) => (v as num).toInt()),
  );
  return val;
}, fieldKeyMap: const {'source_': 'source'});

Map<String, dynamic> _$PracticeTestTopicOptionToJson(
  PracticeTestTopicOption instance,
) => <String, dynamic>{
  'id': instance.id,
  'name': instance.name,
  'source': _$PracticeTestTopicOptionSource_EnumEnumMap[instance.source_]!,
  'available': instance.available,
};

const _$PracticeTestTopicOptionSource_EnumEnumMap = {
  PracticeTestTopicOptionSource_Enum.school: 'school',
  PracticeTestTopicOptionSource_Enum.library_: 'library',
  PracticeTestTopicOptionSource_Enum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
