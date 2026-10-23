// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_grade_level.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicGradeLevel _$AcademicGradeLevelFromJson(Map<String, dynamic> json) =>
    $checkedCreate('AcademicGradeLevel', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['id', 'code', 'name', 'phase', 'sort_order'],
      );
      final val = AcademicGradeLevel(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        code: $checkedConvert('code', (v) => v as String),
        name: $checkedConvert('name', (v) => v as String),
        phase: $checkedConvert(
          'phase',
          (v) => $enumDecode(
            _$AcademicGradeLevelPhaseEnumEnumMap,
            v,
            unknownValue: AcademicGradeLevelPhaseEnum.unknownDefaultOpenApi,
          ),
        ),
        sortOrder: $checkedConvert('sort_order', (v) => (v as num).toInt()),
      );
      return val;
    }, fieldKeyMap: const {'sortOrder': 'sort_order'});

Map<String, dynamic> _$AcademicGradeLevelToJson(AcademicGradeLevel instance) =>
    <String, dynamic>{
      'id': instance.id,
      'code': instance.code,
      'name': instance.name,
      'phase': _$AcademicGradeLevelPhaseEnumEnumMap[instance.phase]!,
      'sort_order': instance.sortOrder,
    };

const _$AcademicGradeLevelPhaseEnumEnumMap = {
  AcademicGradeLevelPhaseEnum.foundation: 'Foundation',
  AcademicGradeLevelPhaseEnum.intermediate: 'Intermediate',
  AcademicGradeLevelPhaseEnum.senior: 'Senior',
  AcademicGradeLevelPhaseEnum.FET: 'FET',
  AcademicGradeLevelPhaseEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
