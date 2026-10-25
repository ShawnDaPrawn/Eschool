// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'tutor_source_choice.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TutorSourceChoice _$TutorSourceChoiceFromJson(Map<String, dynamic> json) =>
    $checkedCreate('TutorSourceChoice', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['id', 'title']);
      final val = TutorSourceChoice(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        title: $checkedConvert('title', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$TutorSourceChoiceToJson(TutorSourceChoice instance) =>
    <String, dynamic>{'id': instance.id, 'title': instance.title};
