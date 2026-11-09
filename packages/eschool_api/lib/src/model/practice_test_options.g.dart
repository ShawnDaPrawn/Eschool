// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_test_options.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeTestOptions _$PracticeTestOptionsFromJson(Map<String, dynamic> json) =>
    $checkedCreate('PracticeTestOptions', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const [
          'group_id',
          'topics',
          'papers',
          'weightings',
          'limits',
          'writable',
        ],
      );
      final val = PracticeTestOptions(
        groupId: $checkedConvert('group_id', (v) => (v as num).toInt()),
        topics: $checkedConvert(
          'topics',
          (v) => (v as List<dynamic>)
              .map(
                (e) =>
                    PracticeTestTopicOption.fromJson(e as Map<String, dynamic>),
              )
              .toList(),
        ),
        papers: $checkedConvert(
          'papers',
          (v) => (v as List<dynamic>)
              .map(
                (e) =>
                    PracticeTestPaperOption.fromJson(e as Map<String, dynamic>),
              )
              .toList(),
        ),
        weightings: $checkedConvert(
          'weightings',
          (v) => (v as List<dynamic>)
              .map(
                (e) => $enumDecode(
                  _$PracticeTestOptionsWeightingsEnumEnumMap,
                  e,
                  unknownValue:
                      PracticeTestOptionsWeightingsEnum.unknownDefaultOpenApi,
                ),
              )
              .toList(),
        ),
        limits: $checkedConvert(
          'limits',
          (v) => PracticeTestOptionsLimits.fromJson(v as Map<String, dynamic>),
        ),
        writable: $checkedConvert('writable', (v) => v as bool),
      );
      return val;
    }, fieldKeyMap: const {'groupId': 'group_id'});

Map<String, dynamic> _$PracticeTestOptionsToJson(
  PracticeTestOptions instance,
) => <String, dynamic>{
  'group_id': instance.groupId,
  'topics': instance.topics.map((e) => e.toJson()).toList(),
  'papers': instance.papers.map((e) => e.toJson()).toList(),
  'weightings': instance.weightings
      .map((e) => _$PracticeTestOptionsWeightingsEnumEnumMap[e]!)
      .toList(),
  'limits': instance.limits.toJson(),
  'writable': instance.writable,
};

const _$PracticeTestOptionsWeightingsEnumEnumMap = {
  PracticeTestOptionsWeightingsEnum.even: 'even',
  PracticeTestOptionsWeightingsEnum.pastPapers: 'past_papers',
  PracticeTestOptionsWeightingsEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
