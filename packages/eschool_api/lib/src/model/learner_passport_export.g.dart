// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learner_passport_export.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearnerPassportExport _$LearnerPassportExportFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('LearnerPassportExport', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['format', 'generated_at', 'passport']);
  final val = LearnerPassportExport(
    format: $checkedConvert(
      'format',
      (v) => $enumDecode(
        _$LearnerPassportExportFormatEnumEnumMap,
        v,
        unknownValue: LearnerPassportExportFormatEnum.unknownDefaultOpenApi,
      ),
    ),
    generatedAt: $checkedConvert(
      'generated_at',
      (v) => DateTime.parse(v as String),
    ),
    passport: $checkedConvert(
      'passport',
      (v) => LearnerPassportExportPassport.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
}, fieldKeyMap: const {'generatedAt': 'generated_at'});

Map<String, dynamic> _$LearnerPassportExportToJson(
  LearnerPassportExport instance,
) => <String, dynamic>{
  'format': _$LearnerPassportExportFormatEnumEnumMap[instance.format]!,
  'generated_at': instance.generatedAt.toIso8601String(),
  'passport': instance.passport.toJson(),
};

const _$LearnerPassportExportFormatEnumEnumMap = {
  LearnerPassportExportFormatEnum.navigateedLearnerPassportV1:
      'navigateed-learner-passport-v1',
  LearnerPassportExportFormatEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
