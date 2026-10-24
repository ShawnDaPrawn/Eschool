// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'subject_standard.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SubjectStandard _$SubjectStandardFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'SubjectStandard',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'framework',
            'code',
            'name',
            'phase',
            'grade_from',
            'grade_to',
            'version',
            'source_url',
            'effective_on',
            'component_weights',
          ],
        );
        final val = SubjectStandard(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          framework: $checkedConvert(
            'framework',
            (v) => $enumDecode(
              _$SubjectStandardFrameworkEnumEnumMap,
              v,
              unknownValue: SubjectStandardFrameworkEnum.unknownDefaultOpenApi,
            ),
          ),
          code: $checkedConvert('code', (v) => v as String),
          name: $checkedConvert('name', (v) => v as String),
          phase: $checkedConvert(
            'phase',
            (v) => $enumDecode(
              _$SubjectStandardPhaseEnumEnumMap,
              v,
              unknownValue: SubjectStandardPhaseEnum.unknownDefaultOpenApi,
            ),
          ),
          gradeFrom: $checkedConvert('grade_from', (v) => (v as num).toInt()),
          gradeTo: $checkedConvert('grade_to', (v) => (v as num).toInt()),
          version: $checkedConvert('version', (v) => v as String),
          sourceUrl: $checkedConvert('source_url', (v) => v as String),
          effectiveOn: $checkedConvert(
            'effective_on',
            (v) => DateTime.parse(v as String),
          ),
          componentWeights: $checkedConvert(
            'component_weights',
            (v) => (v as Map<String, dynamic>?)?.map(
              (k, e) => MapEntry(k, (e as num).toInt()),
            ),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'gradeFrom': 'grade_from',
        'gradeTo': 'grade_to',
        'sourceUrl': 'source_url',
        'effectiveOn': 'effective_on',
        'componentWeights': 'component_weights',
      },
    );

Map<String, dynamic> _$SubjectStandardToJson(SubjectStandard instance) =>
    <String, dynamic>{
      'id': instance.id,
      'framework': _$SubjectStandardFrameworkEnumEnumMap[instance.framework]!,
      'code': instance.code,
      'name': instance.name,
      'phase': _$SubjectStandardPhaseEnumEnumMap[instance.phase]!,
      'grade_from': instance.gradeFrom,
      'grade_to': instance.gradeTo,
      'version': instance.version,
      'source_url': instance.sourceUrl,
      'effective_on': instance.effectiveOn.toIso8601String(),
      'component_weights': instance.componentWeights,
    };

const _$SubjectStandardFrameworkEnumEnumMap = {
  SubjectStandardFrameworkEnum.CAPS: 'CAPS',
  SubjectStandardFrameworkEnum.IEB: 'IEB',
  SubjectStandardFrameworkEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$SubjectStandardPhaseEnumEnumMap = {
  SubjectStandardPhaseEnum.foundation: 'Foundation',
  SubjectStandardPhaseEnum.intermediate: 'Intermediate',
  SubjectStandardPhaseEnum.senior: 'Senior',
  SubjectStandardPhaseEnum.FET: 'FET',
  SubjectStandardPhaseEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
