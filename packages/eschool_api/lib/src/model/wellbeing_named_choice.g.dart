// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wellbeing_named_choice.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WellbeingNamedChoice _$WellbeingNamedChoiceFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('WellbeingNamedChoice', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'name']);
  final val = WellbeingNamedChoice(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    name: $checkedConvert('name', (v) => v as String),
  );
  return val;
});

Map<String, dynamic> _$WellbeingNamedChoiceToJson(
  WellbeingNamedChoice instance,
) => <String, dynamic>{'id': instance.id, 'name': instance.name};
