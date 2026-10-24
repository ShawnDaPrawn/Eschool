// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_subject_standard.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolSubjectStandard _$SchoolSubjectStandardFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'SchoolSubjectStandard',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'subject_id',
        'name',
        'code',
        'type',
        'medium_id',
        'standard_id',
        'standard',
      ],
    );
    final val = SchoolSubjectStandard(
      subjectId: $checkedConvert('subject_id', (v) => (v as num).toInt()),
      name: $checkedConvert('name', (v) => v as String),
      code: $checkedConvert('code', (v) => v as String?),
      type: $checkedConvert('type', (v) => v as String),
      mediumId: $checkedConvert('medium_id', (v) => (v as num).toInt()),
      standardId: $checkedConvert('standard_id', (v) => (v as num?)?.toInt()),
      standard: $checkedConvert(
        'standard',
        (v) => v == null
            ? null
            : SubjectStandard.fromJson(v as Map<String, dynamic>),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'subjectId': 'subject_id',
    'mediumId': 'medium_id',
    'standardId': 'standard_id',
  },
);

Map<String, dynamic> _$SchoolSubjectStandardToJson(
  SchoolSubjectStandard instance,
) => <String, dynamic>{
  'subject_id': instance.subjectId,
  'name': instance.name,
  'code': instance.code,
  'type': instance.type,
  'medium_id': instance.mediumId,
  'standard_id': instance.standardId,
  'standard': instance.standard?.toJson(),
};
