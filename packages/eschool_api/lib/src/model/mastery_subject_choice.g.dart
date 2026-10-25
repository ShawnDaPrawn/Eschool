// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'mastery_subject_choice.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

MasterySubjectChoice _$MasterySubjectChoiceFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('MasterySubjectChoice', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'name']);
  final val = MasterySubjectChoice(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    name: $checkedConvert('name', (v) => v as String),
  );
  return val;
});

Map<String, dynamic> _$MasterySubjectChoiceToJson(
  MasterySubjectChoice instance,
) => <String, dynamic>{'id': instance.id, 'name': instance.name};
