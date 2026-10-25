// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_framework_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineFrameworkWrite _$DisciplineFrameworkWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'DisciplineFrameworkWrite',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'session_year_id',
        'expected_revision',
        'title',
        'counter_scope',
        'reset',
        'levels',
        'sanctions',
        'infractions',
      ],
    );
    final val = DisciplineFrameworkWrite(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      expectedRevision: $checkedConvert(
        'expected_revision',
        (v) => (v as num).toInt(),
      ),
      title: $checkedConvert('title', (v) => v as String),
      counterScope: $checkedConvert(
        'counter_scope',
        (v) => $enumDecode(
          _$DisciplineFrameworkWriteCounterScopeEnumEnumMap,
          v,
          unknownValue:
              DisciplineFrameworkWriteCounterScopeEnum.unknownDefaultOpenApi,
        ),
      ),
      reset: $checkedConvert(
        'reset',
        (v) => $enumDecode(
          _$DisciplineFrameworkWriteResetEnumEnumMap,
          v,
          unknownValue: DisciplineFrameworkWriteResetEnum.unknownDefaultOpenApi,
        ),
      ),
      rollingDays: $checkedConvert('rolling_days', (v) => (v as num?)?.toInt()),
      levels: $checkedConvert(
        'levels',
        (v) => (v as List<dynamic>)
            .map((e) => DisciplineLevel.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      sanctions: $checkedConvert(
        'sanctions',
        (v) => (v as List<dynamic>)
            .map((e) => DisciplineSanction.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      infractions: $checkedConvert(
        'infractions',
        (v) => (v as List<dynamic>)
            .map(
              (e) => DisciplineInfraction.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'sessionYearId': 'session_year_id',
    'expectedRevision': 'expected_revision',
    'counterScope': 'counter_scope',
    'rollingDays': 'rolling_days',
  },
);

Map<String, dynamic> _$DisciplineFrameworkWriteToJson(
  DisciplineFrameworkWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'session_year_id': instance.sessionYearId,
  'expected_revision': instance.expectedRevision,
  'title': instance.title,
  'counter_scope':
      _$DisciplineFrameworkWriteCounterScopeEnumEnumMap[instance.counterScope]!,
  'reset': _$DisciplineFrameworkWriteResetEnumEnumMap[instance.reset]!,
  'rolling_days': ?instance.rollingDays,
  'levels': instance.levels.map((e) => e.toJson()).toList(),
  'sanctions': instance.sanctions.map((e) => e.toJson()).toList(),
  'infractions': instance.infractions.map((e) => e.toJson()).toList(),
};

const _$DisciplineFrameworkWriteCounterScopeEnumEnumMap = {
  DisciplineFrameworkWriteCounterScopeEnum.sameInfraction: 'same_infraction',
  DisciplineFrameworkWriteCounterScopeEnum.allInfractions: 'all_infractions',
  DisciplineFrameworkWriteCounterScopeEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$DisciplineFrameworkWriteResetEnumEnumMap = {
  DisciplineFrameworkWriteResetEnum.schoolYear: 'school_year',
  DisciplineFrameworkWriteResetEnum.term: 'term',
  DisciplineFrameworkWriteResetEnum.rollingDays: 'rolling_days',
  DisciplineFrameworkWriteResetEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
