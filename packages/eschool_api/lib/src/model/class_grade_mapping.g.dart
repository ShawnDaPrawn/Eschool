// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'class_grade_mapping.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ClassGradeMapping _$ClassGradeMappingFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'ClassGradeMapping',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const ['class_id', 'grade_level_id', 'grade_level'],
        );
        final val = ClassGradeMapping(
          classId: $checkedConvert('class_id', (v) => (v as num).toInt()),
          gradeLevelId: $checkedConvert(
            'grade_level_id',
            (v) => (v as num?)?.toInt(),
          ),
          gradeLevel: $checkedConvert(
            'grade_level',
            (v) => v == null
                ? null
                : ClassGradeMappingGradeLevel.fromJson(
                    v as Map<String, dynamic>,
                  ),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'classId': 'class_id',
        'gradeLevelId': 'grade_level_id',
        'gradeLevel': 'grade_level',
      },
    );

Map<String, dynamic> _$ClassGradeMappingToJson(ClassGradeMapping instance) =>
    <String, dynamic>{
      'class_id': instance.classId,
      'grade_level_id': instance.gradeLevelId,
      'grade_level': instance.gradeLevel?.toJson(),
    };
