// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'teaching_evidence_choice.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TeachingEvidenceChoice _$TeachingEvidenceChoiceFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TeachingEvidenceChoice', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'name']);
  final val = TeachingEvidenceChoice(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    name: $checkedConvert('name', (v) => v as String),
  );
  return val;
});

Map<String, dynamic> _$TeachingEvidenceChoiceToJson(
  TeachingEvidenceChoice instance,
) => <String, dynamic>{'id': instance.id, 'name': instance.name};
