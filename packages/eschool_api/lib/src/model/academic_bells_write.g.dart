// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_bells_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicBellsWrite _$AcademicBellsWriteFromJson(Map<String, dynamic> json) =>
    $checkedCreate('AcademicBellsWrite', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['name', 'section', 'session_year_id', 'periods'],
      );
      final val = AcademicBellsWrite(
        name: $checkedConvert('name', (v) => v as String),
        section: $checkedConvert('section', (v) => v as String),
        sessionYearId: $checkedConvert(
          'session_year_id',
          (v) => (v as num).toInt(),
        ),
        periods: $checkedConvert(
          'periods',
          (v) => (v as List<dynamic>)
              .map(
                (e) =>
                    AcademicBellPeriodWrite.fromJson(e as Map<String, dynamic>),
              )
              .toList(),
        ),
        revision: $checkedConvert('revision', (v) => (v as num?)?.toInt()),
      );
      return val;
    }, fieldKeyMap: const {'sessionYearId': 'session_year_id'});

Map<String, dynamic> _$AcademicBellsWriteToJson(AcademicBellsWrite instance) =>
    <String, dynamic>{
      'name': instance.name,
      'section': instance.section,
      'session_year_id': instance.sessionYearId,
      'periods': instance.periods.map((e) => e.toJson()).toList(),
      'revision': ?instance.revision,
    };
