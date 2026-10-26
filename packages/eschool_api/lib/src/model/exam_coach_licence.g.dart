// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_licence.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachLicence _$ExamCoachLicenceFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ExamCoachLicence', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['kind', 'reference', 'statement', 'confirmed'],
      );
      final val = ExamCoachLicence(
        kind: $checkedConvert(
          'kind',
          (v) => $enumDecode(
            _$ExamCoachLicenceKindEnumEnumMap,
            v,
            unknownValue: ExamCoachLicenceKindEnum.unknownDefaultOpenApi,
          ),
        ),
        reference: $checkedConvert('reference', (v) => v as String),
        statement: $checkedConvert('statement', (v) => v as String),
        confirmed: $checkedConvert('confirmed', (v) => v as bool),
        expiresOn: $checkedConvert('expires_on', (v) => v as String?),
      );
      return val;
    }, fieldKeyMap: const {'expiresOn': 'expires_on'});

Map<String, dynamic> _$ExamCoachLicenceToJson(ExamCoachLicence instance) =>
    <String, dynamic>{
      'kind': _$ExamCoachLicenceKindEnumEnumMap[instance.kind]!,
      'reference': instance.reference,
      'statement': instance.statement,
      'confirmed': instance.confirmed,
      'expires_on': ?instance.expiresOn,
    };

const _$ExamCoachLicenceKindEnumEnumMap = {
  ExamCoachLicenceKindEnum.schoolOwned: 'school_owned',
  ExamCoachLicenceKindEnum.openLicence: 'open_licence',
  ExamCoachLicenceKindEnum.permission: 'permission',
  ExamCoachLicenceKindEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
