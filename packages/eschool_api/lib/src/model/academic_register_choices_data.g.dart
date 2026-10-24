// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_register_choices_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicRegisterChoicesData _$AcademicRegisterChoicesDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicRegisterChoicesData',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'on_date',
        'session_year_id',
        'school_years',
        'homerooms',
        'periods',
        'can_manage',
        'settings',
      ],
    );
    final val = AcademicRegisterChoicesData(
      onDate: $checkedConvert('on_date', (v) => v as String),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      schoolYears: $checkedConvert(
        'school_years',
        (v) => (v as List<dynamic>)
            .map(
              (e) =>
                  AcademicTimetableChoice.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      homerooms: $checkedConvert(
        'homerooms',
        (v) => (v as List<dynamic>)
            .map(
              (e) =>
                  AcademicTimetableChoice.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      periods: $checkedConvert(
        'periods',
        (v) => (v as List<dynamic>)
            .map(
              (e) =>
                  AcademicTimetableChoice.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      canManage: $checkedConvert('can_manage', (v) => v as bool),
      settings: $checkedConvert(
        'settings',
        (v) => AcademicRegisterSettingsData.fromJson(v as Map<String, dynamic>),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'onDate': 'on_date',
    'sessionYearId': 'session_year_id',
    'schoolYears': 'school_years',
    'canManage': 'can_manage',
  },
);

Map<String, dynamic> _$AcademicRegisterChoicesDataToJson(
  AcademicRegisterChoicesData instance,
) => <String, dynamic>{
  'on_date': instance.onDate,
  'session_year_id': instance.sessionYearId,
  'school_years': instance.schoolYears.map((e) => e.toJson()).toList(),
  'homerooms': instance.homerooms.map((e) => e.toJson()).toList(),
  'periods': instance.periods.map((e) => e.toJson()).toList(),
  'can_manage': instance.canManage,
  'settings': instance.settings.toJson(),
};
