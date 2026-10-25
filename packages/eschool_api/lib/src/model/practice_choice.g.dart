// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_choice.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeChoice _$PracticeChoiceFromJson(Map<String, dynamic> json) =>
    $checkedCreate('PracticeChoice', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['id', 'name']);
      final val = PracticeChoice(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$PracticeChoiceToJson(PracticeChoice instance) =>
    <String, dynamic>{'id': instance.id, 'name': instance.name};
