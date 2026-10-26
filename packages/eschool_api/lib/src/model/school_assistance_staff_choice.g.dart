// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_assistance_staff_choice.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolAssistanceStaffChoice _$SchoolAssistanceStaffChoiceFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SchoolAssistanceStaffChoice', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'name']);
  final val = SchoolAssistanceStaffChoice(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    name: $checkedConvert('name', (v) => v as String),
  );
  return val;
});

Map<String, dynamic> _$SchoolAssistanceStaffChoiceToJson(
  SchoolAssistanceStaffChoice instance,
) => <String, dynamic>{'id': instance.id, 'name': instance.name};
