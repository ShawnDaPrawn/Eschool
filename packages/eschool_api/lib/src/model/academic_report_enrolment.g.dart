// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_enrolment.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportEnrolment _$AcademicReportEnrolmentFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicReportEnrolment', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'starts_on', 'ends_on']);
  final val = AcademicReportEnrolment(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    startsOn: $checkedConvert('starts_on', (v) => v as String),
    endsOn: $checkedConvert('ends_on', (v) => v as String?),
  );
  return val;
}, fieldKeyMap: const {'startsOn': 'starts_on', 'endsOn': 'ends_on'});

Map<String, dynamic> _$AcademicReportEnrolmentToJson(
  AcademicReportEnrolment instance,
) => <String, dynamic>{
  'id': instance.id,
  'starts_on': instance.startsOn,
  'ends_on': instance.endsOn,
};
