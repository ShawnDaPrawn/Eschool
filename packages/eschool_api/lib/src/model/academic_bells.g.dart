// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_bells.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicBells _$AcademicBellsFromJson(Map<String, dynamic> json) =>
    $checkedCreate('AcademicBells', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const [
          'name',
          'section',
          'session_year_id',
          'periods',
          'id',
          'revision',
        ],
      );
      final val = AcademicBells(
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
                (e) => AcademicBellPeriod.fromJson(e as Map<String, dynamic>),
              )
              .toList(),
        ),
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      );
      return val;
    }, fieldKeyMap: const {'sessionYearId': 'session_year_id'});

Map<String, dynamic> _$AcademicBellsToJson(AcademicBells instance) =>
    <String, dynamic>{
      'name': instance.name,
      'section': instance.section,
      'session_year_id': instance.sessionYearId,
      'periods': instance.periods.map((e) => e.toJson()).toList(),
      'id': instance.id,
      'revision': instance.revision,
    };
