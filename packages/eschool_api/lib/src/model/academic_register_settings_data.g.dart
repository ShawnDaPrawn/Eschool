// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_register_settings_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicRegisterSettingsData _$AcademicRegisterSettingsDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicRegisterSettingsData',
  json,
  ($checkedConvert) {
    $checkKeys(json, requiredKeys: const ['absence_cutoff', 'teaching_days']);
    final val = AcademicRegisterSettingsData(
      absenceCutoff: $checkedConvert('absence_cutoff', (v) => v as String),
      teachingDays: $checkedConvert(
        'teaching_days',
        (v) => (v as List<dynamic>).map((e) => (e as num).toInt()).toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'absenceCutoff': 'absence_cutoff',
    'teachingDays': 'teaching_days',
  },
);

Map<String, dynamic> _$AcademicRegisterSettingsDataToJson(
  AcademicRegisterSettingsData instance,
) => <String, dynamic>{
  'absence_cutoff': instance.absenceCutoff,
  'teaching_days': instance.teachingDays,
};
