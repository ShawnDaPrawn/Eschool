// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_term.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportTerm _$AcademicReportTermFromJson(Map<String, dynamic> json) =>
    $checkedCreate('AcademicReportTerm', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['id', 'name', 'grade_from', 'grade_to'],
      );
      final val = AcademicReportTerm(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
        gradeFrom: $checkedConvert('grade_from', (v) => (v as num).toInt()),
        gradeTo: $checkedConvert('grade_to', (v) => (v as num).toInt()),
      );
      return val;
    }, fieldKeyMap: const {'gradeFrom': 'grade_from', 'gradeTo': 'grade_to'});

Map<String, dynamic> _$AcademicReportTermToJson(AcademicReportTerm instance) =>
    <String, dynamic>{
      'id': instance.id,
      'name': instance.name,
      'grade_from': instance.gradeFrom,
      'grade_to': instance.gradeTo,
    };
