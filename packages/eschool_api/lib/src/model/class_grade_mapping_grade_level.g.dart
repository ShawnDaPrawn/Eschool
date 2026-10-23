// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'class_grade_mapping_grade_level.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ClassGradeMappingGradeLevel _$ClassGradeMappingGradeLevelFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ClassGradeMappingGradeLevel', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['id', 'code', 'name', 'phase', 'sort_order'],
  );
  final val = ClassGradeMappingGradeLevel(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    code: $checkedConvert('code', (v) => v as String),
    name: $checkedConvert('name', (v) => v as String),
    phase: $checkedConvert(
      'phase',
      (v) => $enumDecode(
        _$ClassGradeMappingGradeLevelPhaseEnumEnumMap,
        v,
        unknownValue:
            ClassGradeMappingGradeLevelPhaseEnum.unknownDefaultOpenApi,
      ),
    ),
    sortOrder: $checkedConvert('sort_order', (v) => (v as num).toInt()),
  );
  return val;
}, fieldKeyMap: const {'sortOrder': 'sort_order'});

Map<String, dynamic> _$ClassGradeMappingGradeLevelToJson(
  ClassGradeMappingGradeLevel instance,
) => <String, dynamic>{
  'id': instance.id,
  'code': instance.code,
  'name': instance.name,
  'phase': _$ClassGradeMappingGradeLevelPhaseEnumEnumMap[instance.phase]!,
  'sort_order': instance.sortOrder,
};

const _$ClassGradeMappingGradeLevelPhaseEnumEnumMap = {
  ClassGradeMappingGradeLevelPhaseEnum.foundation: 'Foundation',
  ClassGradeMappingGradeLevelPhaseEnum.intermediate: 'Intermediate',
  ClassGradeMappingGradeLevelPhaseEnum.senior: 'Senior',
  ClassGradeMappingGradeLevelPhaseEnum.FET: 'FET',
  ClassGradeMappingGradeLevelPhaseEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
