// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learner_passport_export_passport.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearnerPassportExportPassport _$LearnerPassportExportPassportFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('LearnerPassportExportPassport', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['learner', 'scope', 'schools', 'transfers', 'reading'],
  );
  final val = LearnerPassportExportPassport(
    learner: $checkedConvert(
      'learner',
      (v) => LearnerPassportLearner.fromJson(v as Map<String, dynamic>),
    ),
    scope: $checkedConvert(
      'scope',
      (v) => $enumDecode(
        _$LearnerPassportExportPassportScopeEnumEnumMap,
        v,
        unknownValue:
            LearnerPassportExportPassportScopeEnum.unknownDefaultOpenApi,
      ),
    ),
    schools: $checkedConvert(
      'schools',
      (v) => (v as List<dynamic>)
          .map((e) => LearnerPassportSchool.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
    transfers: $checkedConvert(
      'transfers',
      (v) => (v as List<dynamic>)
          .map(
            (e) => LearnerPassportTransfer.fromJson(e as Map<String, dynamic>),
          )
          .toList(),
    ),
    reading: $checkedConvert(
      'reading',
      (v) => LearnerPassportReading.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$LearnerPassportExportPassportToJson(
  LearnerPassportExportPassport instance,
) => <String, dynamic>{
  'learner': instance.learner.toJson(),
  'scope': _$LearnerPassportExportPassportScopeEnumEnumMap[instance.scope]!,
  'schools': instance.schools.map((e) => e.toJson()).toList(),
  'transfers': instance.transfers.map((e) => e.toJson()).toList(),
  'reading': instance.reading.toJson(),
};

const _$LearnerPassportExportPassportScopeEnumEnumMap = {
  LearnerPassportExportPassportScopeEnum.allSchools: 'all_schools',
  LearnerPassportExportPassportScopeEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
