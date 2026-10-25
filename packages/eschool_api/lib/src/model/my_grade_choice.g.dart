// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'my_grade_choice.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

MyGradeChoice _$MyGradeChoiceFromJson(Map<String, dynamic> json) =>
    $checkedCreate('MyGradeChoice', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['id', 'name']);
      final val = MyGradeChoice(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$MyGradeChoiceToJson(MyGradeChoice instance) =>
    <String, dynamic>{'id': instance.id, 'name': instance.name};
