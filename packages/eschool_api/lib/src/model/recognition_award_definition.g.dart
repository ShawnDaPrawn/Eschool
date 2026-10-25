// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'recognition_award_definition.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RecognitionAwardDefinition _$RecognitionAwardDefinitionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('RecognitionAwardDefinition', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const [
      'id',
      'award_category_id',
      'name',
      'criteria',
      'frequency',
      'active',
      'revision',
    ],
  );
  final val = RecognitionAwardDefinition(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    awardCategoryId: $checkedConvert(
      'award_category_id',
      (v) => (v as num).toInt(),
    ),
    name: $checkedConvert('name', (v) => v as String),
    criteria: $checkedConvert('criteria', (v) => v as String),
    frequency: $checkedConvert('frequency', (v) => v as String),
    active: $checkedConvert('active', (v) => v as bool),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
  );
  return val;
}, fieldKeyMap: const {'awardCategoryId': 'award_category_id'});

Map<String, dynamic> _$RecognitionAwardDefinitionToJson(
  RecognitionAwardDefinition instance,
) => <String, dynamic>{
  'id': instance.id,
  'award_category_id': instance.awardCategoryId,
  'name': instance.name,
  'criteria': instance.criteria,
  'frequency': instance.frequency,
  'active': instance.active,
  'revision': instance.revision,
};
