// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_homeroom.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportHomeroom _$AcademicReportHomeroomFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicReportHomeroom',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['id', 'grade_level_id', 'grade_order', 'name'],
    );
    final val = AcademicReportHomeroom(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      gradeLevelId: $checkedConvert(
        'grade_level_id',
        (v) => (v as num).toInt(),
      ),
      gradeOrder: $checkedConvert('grade_order', (v) => (v as num).toInt()),
      name: $checkedConvert('name', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'gradeLevelId': 'grade_level_id',
    'gradeOrder': 'grade_order',
  },
);

Map<String, dynamic> _$AcademicReportHomeroomToJson(
  AcademicReportHomeroom instance,
) => <String, dynamic>{
  'id': instance.id,
  'grade_level_id': instance.gradeLevelId,
  'grade_order': instance.gradeOrder,
  'name': instance.name,
};
