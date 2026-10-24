// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_transition_homeroom.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicTransitionHomeroom _$AcademicTransitionHomeroomFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicTransitionHomeroom', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'name', 'grade_level_id']);
  final val = AcademicTransitionHomeroom(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    name: $checkedConvert('name', (v) => v as String),
    gradeLevelId: $checkedConvert(
      'grade_level_id',
      (v) => (v as num?)?.toInt(),
    ),
  );
  return val;
}, fieldKeyMap: const {'gradeLevelId': 'grade_level_id'});

Map<String, dynamic> _$AcademicTransitionHomeroomToJson(
  AcademicTransitionHomeroom instance,
) => <String, dynamic>{
  'id': instance.id,
  'name': instance.name,
  'grade_level_id': instance.gradeLevelId,
};
