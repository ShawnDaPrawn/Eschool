// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_form_group_choice.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyFormGroupChoice _$FamilyFormGroupChoiceFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('FamilyFormGroupChoice', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'name', 'session_year_id']);
  final val = FamilyFormGroupChoice(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    name: $checkedConvert('name', (v) => v as String),
    sessionYearId: $checkedConvert(
      'session_year_id',
      (v) => (v as num).toInt(),
    ),
  );
  return val;
}, fieldKeyMap: const {'sessionYearId': 'session_year_id'});

Map<String, dynamic> _$FamilyFormGroupChoiceToJson(
  FamilyFormGroupChoice instance,
) => <String, dynamic>{
  'id': instance.id,
  'name': instance.name,
  'session_year_id': instance.sessionYearId,
};
