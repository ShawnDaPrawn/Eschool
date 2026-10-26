// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'teaching_group_choice.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TeachingGroupChoice _$TeachingGroupChoiceFromJson(Map<String, dynamic> json) =>
    $checkedCreate('TeachingGroupChoice', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['id', 'name', 'subject_name']);
      final val = TeachingGroupChoice(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
        subjectName: $checkedConvert('subject_name', (v) => v as String),
      );
      return val;
    }, fieldKeyMap: const {'subjectName': 'subject_name'});

Map<String, dynamic> _$TeachingGroupChoiceToJson(
  TeachingGroupChoice instance,
) => <String, dynamic>{
  'id': instance.id,
  'name': instance.name,
  'subject_name': instance.subjectName,
};
