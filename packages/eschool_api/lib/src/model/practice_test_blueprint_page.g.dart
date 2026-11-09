// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_test_blueprint_page.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeTestBlueprintPage _$PracticeTestBlueprintPageFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('PracticeTestBlueprintPage', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['group_id', 'tests', 'page', 'has_more'],
  );
  final val = PracticeTestBlueprintPage(
    groupId: $checkedConvert('group_id', (v) => (v as num).toInt()),
    tests: $checkedConvert(
      'tests',
      (v) => (v as List<dynamic>)
          .map(
            (e) => PracticeTestBlueprintListItem.fromJson(
              e as Map<String, dynamic>,
            ),
          )
          .toList(),
    ),
    page: $checkedConvert('page', (v) => (v as num).toInt()),
    hasMore: $checkedConvert('has_more', (v) => v as bool),
  );
  return val;
}, fieldKeyMap: const {'groupId': 'group_id', 'hasMore': 'has_more'});

Map<String, dynamic> _$PracticeTestBlueprintPageToJson(
  PracticeTestBlueprintPage instance,
) => <String, dynamic>{
  'group_id': instance.groupId,
  'tests': instance.tests.map((e) => e.toJson()).toList(),
  'page': instance.page,
  'has_more': instance.hasMore,
};
