// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_timetable_configuration_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicTimetableConfigurationData _$AcademicTimetableConfigurationDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicTimetableConfigurationData',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['rooms', 'bell_schedules', 'teachers', 'homerooms'],
    );
    final val = AcademicTimetableConfigurationData(
      schoolYears: $checkedConvert(
        'school_years',
        (v) => (v as List<dynamic>?)
            ?.map(
              (e) =>
                  AcademicTimetableChoice.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      rooms: $checkedConvert(
        'rooms',
        (v) => (v as List<dynamic>)
            .map((e) => AcademicRoom.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      bellSchedules: $checkedConvert(
        'bell_schedules',
        (v) => (v as List<dynamic>)
            .map((e) => AcademicBells.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      teachers: $checkedConvert(
        'teachers',
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
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolYears': 'school_years',
    'bellSchedules': 'bell_schedules',
  },
);

Map<String, dynamic> _$AcademicTimetableConfigurationDataToJson(
  AcademicTimetableConfigurationData instance,
) => <String, dynamic>{
  'school_years': ?instance.schoolYears?.map((e) => e.toJson()).toList(),
  'rooms': instance.rooms.map((e) => e.toJson()).toList(),
  'bell_schedules': instance.bellSchedules.map((e) => e.toJson()).toList(),
  'teachers': instance.teachers.map((e) => e.toJson()).toList(),
  'homerooms': instance.homerooms.map((e) => e.toJson()).toList(),
};
