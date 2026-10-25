// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_group_choice.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeGroupChoice _$PracticeGroupChoiceFromJson(Map<String, dynamic> json) =>
    $checkedCreate('PracticeGroupChoice', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['id', 'name', 'subject_name']);
      final val = PracticeGroupChoice(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
        subjectName: $checkedConvert('subject_name', (v) => v as String),
        setId: $checkedConvert('set_id', (v) => (v as num?)?.toInt()),
      );
      return val;
    }, fieldKeyMap: const {'subjectName': 'subject_name', 'setId': 'set_id'});

Map<String, dynamic> _$PracticeGroupChoiceToJson(
  PracticeGroupChoice instance,
) => <String, dynamic>{
  'id': instance.id,
  'name': instance.name,
  'subject_name': instance.subjectName,
  'set_id': ?instance.setId,
};
