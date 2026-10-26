// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'tutor_topic_choice.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TutorTopicChoice _$TutorTopicChoiceFromJson(Map<String, dynamic> json) =>
    $checkedCreate('TutorTopicChoice', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['id', 'name']);
      final val = TutorTopicChoice(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$TutorTopicChoiceToJson(TutorTopicChoice instance) =>
    <String, dynamic>{'id': instance.id, 'name': instance.name};
