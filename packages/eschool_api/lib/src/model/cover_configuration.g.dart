// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'cover_configuration.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CoverConfiguration _$CoverConfigurationFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'CoverConfiguration',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'session_year_id',
        'year_name',
        'can_manage',
        'teachers',
        'current_page',
        'last_page',
        'total',
        'absences',
      ],
    );
    final val = CoverConfiguration(
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      yearName: $checkedConvert('year_name', (v) => v as String),
      canManage: $checkedConvert('can_manage', (v) => v as bool),
      teachers: $checkedConvert(
        'teachers',
        (v) => (v as List<dynamic>)
            .map((e) => CoverTeacher.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      currentPage: $checkedConvert('current_page', (v) => (v as num).toInt()),
      lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
      total: $checkedConvert('total', (v) => (v as num).toInt()),
      absences: $checkedConvert(
        'absences',
        (v) => (v as List<dynamic>)
            .map((e) => CoverAbsenceSummary.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'sessionYearId': 'session_year_id',
    'yearName': 'year_name',
    'canManage': 'can_manage',
    'currentPage': 'current_page',
    'lastPage': 'last_page',
  },
);

Map<String, dynamic> _$CoverConfigurationToJson(CoverConfiguration instance) =>
    <String, dynamic>{
      'session_year_id': instance.sessionYearId,
      'year_name': instance.yearName,
      'can_manage': instance.canManage,
      'teachers': instance.teachers.map((e) => e.toJson()).toList(),
      'current_page': instance.currentPage,
      'last_page': instance.lastPage,
      'total': instance.total,
      'absences': instance.absences.map((e) => e.toJson()).toList(),
    };
