// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'teaching_task_choice.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TeachingTaskChoice _$TeachingTaskChoiceFromJson(Map<String, dynamic> json) =>
    $checkedCreate('TeachingTaskChoice', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['id', 'name', 'term_id']);
      final val = TeachingTaskChoice(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
        termId: $checkedConvert('term_id', (v) => (v as num).toInt()),
      );
      return val;
    }, fieldKeyMap: const {'termId': 'term_id'});

Map<String, dynamic> _$TeachingTaskChoiceToJson(TeachingTaskChoice instance) =>
    <String, dynamic>{
      'id': instance.id,
      'name': instance.name,
      'term_id': instance.termId,
    };
