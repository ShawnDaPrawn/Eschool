// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_test_listing_item.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeTestListingItem _$PracticeTestListingItemFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'PracticeTestListingItem',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'blueprint_id',
        'kind',
        'group_id',
        'title',
        'question_count',
        'time_limit_minutes',
        'opens_at',
        'closes_at',
        'window',
        'test',
      ],
    );
    final val = PracticeTestListingItem(
      blueprintId: $checkedConvert('blueprint_id', (v) => (v as num).toInt()),
      kind: $checkedConvert(
        'kind',
        (v) => $enumDecode(
          _$PracticeTestListingItemKindEnumEnumMap,
          v,
          unknownValue: PracticeTestListingItemKindEnum.unknownDefaultOpenApi,
        ),
      ),
      groupId: $checkedConvert('group_id', (v) => (v as num).toInt()),
      title: $checkedConvert('title', (v) => v as String?),
      questionCount: $checkedConvert(
        'question_count',
        (v) => (v as num).toInt(),
      ),
      timeLimitMinutes: $checkedConvert(
        'time_limit_minutes',
        (v) => (v as num?)?.toInt(),
      ),
      opensAt: $checkedConvert(
        'opens_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      closesAt: $checkedConvert(
        'closes_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      window: $checkedConvert(
        'window',
        (v) => $enumDecode(
          _$PracticeTestListingItemWindowEnumEnumMap,
          v,
          unknownValue: PracticeTestListingItemWindowEnum.unknownDefaultOpenApi,
        ),
      ),
      test: $checkedConvert(
        'test',
        (v) => v == null
            ? null
            : PracticeTestListingSitting.fromJson(v as Map<String, dynamic>),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'blueprintId': 'blueprint_id',
    'groupId': 'group_id',
    'questionCount': 'question_count',
    'timeLimitMinutes': 'time_limit_minutes',
    'opensAt': 'opens_at',
    'closesAt': 'closes_at',
  },
);

Map<String, dynamic> _$PracticeTestListingItemToJson(
  PracticeTestListingItem instance,
) => <String, dynamic>{
  'blueprint_id': instance.blueprintId,
  'kind': _$PracticeTestListingItemKindEnumEnumMap[instance.kind]!,
  'group_id': instance.groupId,
  'title': instance.title,
  'question_count': instance.questionCount,
  'time_limit_minutes': instance.timeLimitMinutes,
  'opens_at': instance.opensAt?.toIso8601String(),
  'closes_at': instance.closesAt?.toIso8601String(),
  'window': _$PracticeTestListingItemWindowEnumEnumMap[instance.window]!,
  'test': instance.test?.toJson(),
};

const _$PracticeTestListingItemKindEnumEnumMap = {
  PracticeTestListingItemKindEnum.quickQuiz: 'quick_quiz',
  PracticeTestListingItemKindEnum.cycleTest: 'cycle_test',
  PracticeTestListingItemKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$PracticeTestListingItemWindowEnumEnumMap = {
  PracticeTestListingItemWindowEnum.upcoming: 'upcoming',
  PracticeTestListingItemWindowEnum.open: 'open',
  PracticeTestListingItemWindowEnum.closed: 'closed',
  PracticeTestListingItemWindowEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
