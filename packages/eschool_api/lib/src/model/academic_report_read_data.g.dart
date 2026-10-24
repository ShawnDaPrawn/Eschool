// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_read_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportReadData _$AcademicReportReadDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicReportReadData', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'read_at']);
  final val = AcademicReportReadData(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    readAt: $checkedConvert('read_at', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'readAt': 'read_at'});

Map<String, dynamic> _$AcademicReportReadDataToJson(
  AcademicReportReadData instance,
) => <String, dynamic>{'id': instance.id, 'read_at': instance.readAt};
