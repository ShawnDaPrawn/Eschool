// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_release_period.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReleasePeriod _$AcademicReleasePeriodFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicReleasePeriod',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'session_year_id',
        'year_name',
        'term_id',
        'term_name',
        'released_at',
      ],
    );
    final val = AcademicReleasePeriod(
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      yearName: $checkedConvert('year_name', (v) => v as String),
      termId: $checkedConvert('term_id', (v) => (v as num?)?.toInt()),
      termName: $checkedConvert('term_name', (v) => v as String?),
      releasedAt: $checkedConvert('released_at', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'sessionYearId': 'session_year_id',
    'yearName': 'year_name',
    'termId': 'term_id',
    'termName': 'term_name',
    'releasedAt': 'released_at',
  },
);

Map<String, dynamic> _$AcademicReleasePeriodToJson(
  AcademicReleasePeriod instance,
) => <String, dynamic>{
  'session_year_id': instance.sessionYearId,
  'year_name': instance.yearName,
  'term_id': instance.termId,
  'term_name': instance.termName,
  'released_at': instance.releasedAt,
};
