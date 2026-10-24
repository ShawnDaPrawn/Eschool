// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_signature.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportSignature _$AcademicReportSignatureFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicReportSignature', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['name', 'signed_at', 'revision']);
  final val = AcademicReportSignature(
    name: $checkedConvert('name', (v) => v as String),
    signedAt: $checkedConvert('signed_at', (v) => v as String),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
  );
  return val;
}, fieldKeyMap: const {'signedAt': 'signed_at'});

Map<String, dynamic> _$AcademicReportSignatureToJson(
  AcademicReportSignature instance,
) => <String, dynamic>{
  'name': instance.name,
  'signed_at': instance.signedAt,
  'revision': instance.revision,
};
