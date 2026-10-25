// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_level.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineLevel _$DisciplineLevelFromJson(Map<String, dynamic> json) =>
    $checkedCreate('DisciplineLevel', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['code', 'name', 'threshold', 'kind', 'sanctions'],
      );
      final val = DisciplineLevel(
        code: $checkedConvert('code', (v) => v as String),
        name: $checkedConvert('name', (v) => v as String),
        threshold: $checkedConvert('threshold', (v) => (v as num).toInt()),
        kind: $checkedConvert(
          'kind',
          (v) => $enumDecode(
            _$DisciplineLevelKindEnumEnumMap,
            v,
            unknownValue: DisciplineLevelKindEnum.unknownDefaultOpenApi,
          ),
        ),
        sanctions: $checkedConvert(
          'sanctions',
          (v) => (v as List<dynamic>).map((e) => e as String).toList(),
        ),
      );
      return val;
    });

Map<String, dynamic> _$DisciplineLevelToJson(DisciplineLevel instance) =>
    <String, dynamic>{
      'code': instance.code,
      'name': instance.name,
      'threshold': instance.threshold,
      'kind': _$DisciplineLevelKindEnumEnumMap[instance.kind]!,
      'sanctions': instance.sanctions,
    };

const _$DisciplineLevelKindEnumEnumMap = {
  DisciplineLevelKindEnum.sanction: 'sanction',
  DisciplineLevelKindEnum.hearing: 'hearing',
  DisciplineLevelKindEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
