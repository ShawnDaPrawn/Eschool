// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'teaching_learner_choice.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TeachingLearnerChoice _$TeachingLearnerChoiceFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TeachingLearnerChoice', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'name', 'approved']);
  final val = TeachingLearnerChoice(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    name: $checkedConvert('name', (v) => v as String),
    approved: $checkedConvert('approved', (v) => v as bool),
  );
  return val;
});

Map<String, dynamic> _$TeachingLearnerChoiceToJson(
  TeachingLearnerChoice instance,
) => <String, dynamic>{
  'id': instance.id,
  'name': instance.name,
  'approved': instance.approved,
};
