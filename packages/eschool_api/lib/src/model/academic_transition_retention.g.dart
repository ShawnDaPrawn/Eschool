// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_transition_retention.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicTransitionRetention _$AcademicTransitionRetentionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicTransitionRetention',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'days',
        'maximum_days',
        'revision',
        'educational_records',
      ],
    );
    final val = AcademicTransitionRetention(
      days: $checkedConvert('days', (v) => (v as num).toInt()),
      maximumDays: $checkedConvert('maximum_days', (v) => (v as num).toInt()),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      educationalRecords: $checkedConvert(
        'educational_records',
        (v) => v as String,
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'maximumDays': 'maximum_days',
    'educationalRecords': 'educational_records',
  },
);

Map<String, dynamic> _$AcademicTransitionRetentionToJson(
  AcademicTransitionRetention instance,
) => <String, dynamic>{
  'days': instance.days,
  'maximum_days': instance.maximumDays,
  'revision': instance.revision,
  'educational_records': instance.educationalRecords,
};
