// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'tutor_home_language.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TutorHomeLanguage _$TutorHomeLanguageFromJson(Map<String, dynamic> json) =>
    $checkedCreate('TutorHomeLanguage', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['code', 'name']);
      final val = TutorHomeLanguage(
        code: $checkedConvert(
          'code',
          (v) => $enumDecode(
            _$TutorHomeLanguageCodeEnumEnumMap,
            v,
            unknownValue: TutorHomeLanguageCodeEnum.unknownDefaultOpenApi,
          ),
        ),
        name: $checkedConvert('name', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$TutorHomeLanguageToJson(TutorHomeLanguage instance) =>
    <String, dynamic>{
      'code': _$TutorHomeLanguageCodeEnumEnumMap[instance.code]!,
      'name': instance.name,
    };

const _$TutorHomeLanguageCodeEnumEnumMap = {
  TutorHomeLanguageCodeEnum.af: 'af',
  TutorHomeLanguageCodeEnum.zu: 'zu',
  TutorHomeLanguageCodeEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
