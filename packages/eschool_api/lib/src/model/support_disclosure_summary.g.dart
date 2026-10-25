// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'support_disclosure_summary.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SupportDisclosureSummary _$SupportDisclosureSummaryFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'SupportDisclosureSummary',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'revision',
        'user_id',
        'teacher_name',
        'expires_on',
        'accommodations',
      ],
    );
    final val = SupportDisclosureSummary(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      userId: $checkedConvert('user_id', (v) => (v as num).toInt()),
      teacherName: $checkedConvert('teacher_name', (v) => v as String),
      expiresOn: $checkedConvert('expires_on', (v) => v as String),
      accommodations: $checkedConvert('accommodations', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'userId': 'user_id',
    'teacherName': 'teacher_name',
    'expiresOn': 'expires_on',
  },
);

Map<String, dynamic> _$SupportDisclosureSummaryToJson(
  SupportDisclosureSummary instance,
) => <String, dynamic>{
  'id': instance.id,
  'revision': instance.revision,
  'user_id': instance.userId,
  'teacher_name': instance.teacherName,
  'expires_on': instance.expiresOn,
  'accommodations': instance.accommodations,
};
