// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_transition_year.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicTransitionYear _$AcademicTransitionYearFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicTransitionYear', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'name', 'starts_on', 'ends_on']);
  final val = AcademicTransitionYear(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    name: $checkedConvert('name', (v) => v as String),
    startsOn: $checkedConvert('starts_on', (v) => v as String?),
    endsOn: $checkedConvert('ends_on', (v) => v as String?),
  );
  return val;
}, fieldKeyMap: const {'startsOn': 'starts_on', 'endsOn': 'ends_on'});

Map<String, dynamic> _$AcademicTransitionYearToJson(
  AcademicTransitionYear instance,
) => <String, dynamic>{
  'id': instance.id,
  'name': instance.name,
  'starts_on': instance.startsOn,
  'ends_on': instance.endsOn,
};
