// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learner_passport.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearnerPassport _$LearnerPassportFromJson(Map<String, dynamic> json) =>
    $checkedCreate('LearnerPassport', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const [
          'learner',
          'viewer',
          'scope',
          'schools',
          'transfers',
          'reading',
          'export',
        ],
      );
      final val = LearnerPassport(
        learner: $checkedConvert(
          'learner',
          (v) => LearnerPassportLearner.fromJson(v as Map<String, dynamic>),
        ),
        viewer: $checkedConvert(
          'viewer',
          (v) => $enumDecode(
            _$LearnerPassportViewerEnumEnumMap,
            v,
            unknownValue: LearnerPassportViewerEnum.unknownDefaultOpenApi,
          ),
        ),
        scope: $checkedConvert(
          'scope',
          (v) => $enumDecode(
            _$LearnerPassportScopeEnumEnumMap,
            v,
            unknownValue: LearnerPassportScopeEnum.unknownDefaultOpenApi,
          ),
        ),
        schools: $checkedConvert(
          'schools',
          (v) => (v as List<dynamic>)
              .map(
                (e) =>
                    LearnerPassportSchool.fromJson(e as Map<String, dynamic>),
              )
              .toList(),
        ),
        transfers: $checkedConvert(
          'transfers',
          (v) => (v as List<dynamic>)
              .map(
                (e) =>
                    LearnerPassportTransfer.fromJson(e as Map<String, dynamic>),
              )
              .toList(),
        ),
        reading: $checkedConvert(
          'reading',
          (v) => LearnerPassportReading.fromJson(v as Map<String, dynamic>),
        ),
        export_: $checkedConvert(
          'export',
          (v) => v == null
              ? null
              : LearnerPassportExportAvailability.fromJson(
                  v as Map<String, dynamic>,
                ),
        ),
      );
      return val;
    }, fieldKeyMap: const {'export_': 'export'});

Map<String, dynamic> _$LearnerPassportToJson(LearnerPassport instance) =>
    <String, dynamic>{
      'learner': instance.learner.toJson(),
      'viewer': _$LearnerPassportViewerEnumEnumMap[instance.viewer]!,
      'scope': _$LearnerPassportScopeEnumEnumMap[instance.scope]!,
      'schools': instance.schools.map((e) => e.toJson()).toList(),
      'transfers': instance.transfers.map((e) => e.toJson()).toList(),
      'reading': instance.reading.toJson(),
      'export': instance.export_?.toJson(),
    };

const _$LearnerPassportViewerEnumEnumMap = {
  LearnerPassportViewerEnum.learner: 'learner',
  LearnerPassportViewerEnum.guardian: 'guardian',
  LearnerPassportViewerEnum.staff: 'staff',
  LearnerPassportViewerEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$LearnerPassportScopeEnumEnumMap = {
  LearnerPassportScopeEnum.allSchools: 'all_schools',
  LearnerPassportScopeEnum.thisSchool: 'this_school',
  LearnerPassportScopeEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
