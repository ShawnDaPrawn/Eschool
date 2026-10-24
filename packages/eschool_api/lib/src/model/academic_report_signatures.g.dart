// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_signatures.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportSignatures _$AcademicReportSignaturesFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicReportSignatures', json, ($checkedConvert) {
  final val = AcademicReportSignatures(
    gradeHead: $checkedConvert(
      'grade_head',
      (v) => v == null
          ? null
          : AcademicReportSignature.fromJson(v as Map<String, dynamic>),
    ),
    principal: $checkedConvert(
      'principal',
      (v) => v == null
          ? null
          : AcademicReportSignature.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
}, fieldKeyMap: const {'gradeHead': 'grade_head'});

Map<String, dynamic> _$AcademicReportSignaturesToJson(
  AcademicReportSignatures instance,
) => <String, dynamic>{
  'grade_head': ?instance.gradeHead?.toJson(),
  'principal': ?instance.principal?.toJson(),
};
