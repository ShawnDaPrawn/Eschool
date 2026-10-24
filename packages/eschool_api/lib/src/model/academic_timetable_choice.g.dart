// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_timetable_choice.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicTimetableChoice _$AcademicTimetableChoiceFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicTimetableChoice', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'name']);
  final val = AcademicTimetableChoice(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    name: $checkedConvert('name', (v) => v as String),
  );
  return val;
});

Map<String, dynamic> _$AcademicTimetableChoiceToJson(
  AcademicTimetableChoice instance,
) => <String, dynamic>{'id': instance.id, 'name': instance.name};
