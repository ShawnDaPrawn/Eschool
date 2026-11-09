// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'child_mode_session_school.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ChildModeSessionSchool _$ChildModeSessionSchoolFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ChildModeSessionSchool', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'name']);
  final val = ChildModeSessionSchool(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    name: $checkedConvert('name', (v) => v as String),
  );
  return val;
});

Map<String, dynamic> _$ChildModeSessionSchoolToJson(
  ChildModeSessionSchool instance,
) => <String, dynamic>{'id': instance.id, 'name': instance.name};
