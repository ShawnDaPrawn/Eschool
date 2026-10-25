// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'cover_teacher.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CoverTeacher _$CoverTeacherFromJson(Map<String, dynamic> json) =>
    $checkedCreate('CoverTeacher', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['id', 'name']);
      final val = CoverTeacher(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$CoverTeacherToJson(CoverTeacher instance) =>
    <String, dynamic>{'id': instance.id, 'name': instance.name};
