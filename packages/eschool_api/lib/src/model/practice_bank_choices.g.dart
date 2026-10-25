// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_bank_choices.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeBankChoices _$PracticeBankChoicesFromJson(Map<String, dynamic> json) =>
    $checkedCreate('PracticeBankChoices', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['group_id', 'topics', 'sources', 'writable'],
      );
      final val = PracticeBankChoices(
        groupId: $checkedConvert('group_id', (v) => (v as num).toInt()),
        topics: $checkedConvert(
          'topics',
          (v) => (v as List<dynamic>)
              .map((e) => PracticeChoice.fromJson(e as Map<String, dynamic>))
              .toList(),
        ),
        sources: $checkedConvert(
          'sources',
          (v) => (v as List<dynamic>)
              .map((e) => PracticeChoice.fromJson(e as Map<String, dynamic>))
              .toList(),
        ),
        writable: $checkedConvert('writable', (v) => v as bool),
      );
      return val;
    }, fieldKeyMap: const {'groupId': 'group_id'});

Map<String, dynamic> _$PracticeBankChoicesToJson(
  PracticeBankChoices instance,
) => <String, dynamic>{
  'group_id': instance.groupId,
  'topics': instance.topics.map((e) => e.toJson()).toList(),
  'sources': instance.sources.map((e) => e.toJson()).toList(),
  'writable': instance.writable,
};
