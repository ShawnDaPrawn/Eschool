// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'class_grade_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ClassGradeWrite _$ClassGradeWriteFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ClassGradeWrite', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['grade_level_id']);
      final val = ClassGradeWrite(
        gradeLevelId: $checkedConvert(
          'grade_level_id',
          (v) => (v as num?)?.toInt(),
        ),
      );
      return val;
    }, fieldKeyMap: const {'gradeLevelId': 'grade_level_id'});

Map<String, dynamic> _$ClassGradeWriteToJson(ClassGradeWrite instance) =>
    <String, dynamic>{'grade_level_id': instance.gradeLevelId};
