// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_subject_from_standard_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolSubjectFromStandardWrite _$SchoolSubjectFromStandardWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'SchoolSubjectFromStandardWrite',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['standard_id', 'medium_id', 'type', 'bg_color'],
    );
    final val = SchoolSubjectFromStandardWrite(
      standardId: $checkedConvert('standard_id', (v) => (v as num).toInt()),
      mediumId: $checkedConvert('medium_id', (v) => (v as num).toInt()),
      type: $checkedConvert(
        'type',
        (v) => $enumDecode(
          _$SchoolSubjectFromStandardWriteTypeEnumEnumMap,
          v,
          unknownValue:
              SchoolSubjectFromStandardWriteTypeEnum.unknownDefaultOpenApi,
        ),
      ),
      bgColor: $checkedConvert('bg_color', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'standardId': 'standard_id',
    'mediumId': 'medium_id',
    'bgColor': 'bg_color',
  },
);

Map<String, dynamic> _$SchoolSubjectFromStandardWriteToJson(
  SchoolSubjectFromStandardWrite instance,
) => <String, dynamic>{
  'standard_id': instance.standardId,
  'medium_id': instance.mediumId,
  'type': _$SchoolSubjectFromStandardWriteTypeEnumEnumMap[instance.type]!,
  'bg_color': instance.bgColor,
};

const _$SchoolSubjectFromStandardWriteTypeEnumEnumMap = {
  SchoolSubjectFromStandardWriteTypeEnum.theory: 'Theory',
  SchoolSubjectFromStandardWriteTypeEnum.practical: 'Practical',
  SchoolSubjectFromStandardWriteTypeEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
