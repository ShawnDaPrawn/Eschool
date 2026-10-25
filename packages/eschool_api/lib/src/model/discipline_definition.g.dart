// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_definition.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineDefinition _$DisciplineDefinitionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'DisciplineDefinition',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'counter_scope',
        'reset',
        'levels',
        'sanctions',
        'infractions',
      ],
    );
    final val = DisciplineDefinition(
      counterScope: $checkedConvert(
        'counter_scope',
        (v) => $enumDecode(
          _$DisciplineDefinitionCounterScopeEnumEnumMap,
          v,
          unknownValue:
              DisciplineDefinitionCounterScopeEnum.unknownDefaultOpenApi,
        ),
      ),
      reset: $checkedConvert(
        'reset',
        (v) => $enumDecode(
          _$DisciplineDefinitionResetEnumEnumMap,
          v,
          unknownValue: DisciplineDefinitionResetEnum.unknownDefaultOpenApi,
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
    'counterScope': 'counter_scope',
    'rollingDays': 'rolling_days',
  },
);

Map<String, dynamic> _$DisciplineDefinitionToJson(
  DisciplineDefinition instance,
) => <String, dynamic>{
  'counter_scope':
      _$DisciplineDefinitionCounterScopeEnumEnumMap[instance.counterScope]!,
  'reset': _$DisciplineDefinitionResetEnumEnumMap[instance.reset]!,
  'rolling_days': ?instance.rollingDays,
  'levels': instance.levels.map((e) => e.toJson()).toList(),
  'sanctions': instance.sanctions.map((e) => e.toJson()).toList(),
  'infractions': instance.infractions.map((e) => e.toJson()).toList(),
};

const _$DisciplineDefinitionCounterScopeEnumEnumMap = {
  DisciplineDefinitionCounterScopeEnum.sameInfraction: 'same_infraction',
  DisciplineDefinitionCounterScopeEnum.allInfractions: 'all_infractions',
  DisciplineDefinitionCounterScopeEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$DisciplineDefinitionResetEnumEnumMap = {
  DisciplineDefinitionResetEnum.schoolYear: 'school_year',
  DisciplineDefinitionResetEnum.term: 'term',
  DisciplineDefinitionResetEnum.rollingDays: 'rolling_days',
  DisciplineDefinitionResetEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
