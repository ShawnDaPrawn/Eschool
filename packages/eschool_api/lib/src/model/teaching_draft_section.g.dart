// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'teaching_draft_section.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TeachingDraftSection _$TeachingDraftSectionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TeachingDraftSection', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['level', 'body']);
  final val = TeachingDraftSection(
    level: $checkedConvert(
      'level',
      (v) => $enumDecode(
        _$TeachingDraftSectionLevelEnumEnumMap,
        v,
        unknownValue: TeachingDraftSectionLevelEnum.unknownDefaultOpenApi,
      ),
    ),
    body: $checkedConvert('body', (v) => v as String),
  );
  return val;
});

Map<String, dynamic> _$TeachingDraftSectionToJson(
  TeachingDraftSection instance,
) => <String, dynamic>{
  'level': _$TeachingDraftSectionLevelEnumEnumMap[instance.level]!,
  'body': instance.body,
};

const _$TeachingDraftSectionLevelEnumEnumMap = {
  TeachingDraftSectionLevelEnum.support: 'support',
  TeachingDraftSectionLevelEnum.core: 'core',
  TeachingDraftSectionLevelEnum.extension_: 'extension',
  TeachingDraftSectionLevelEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
