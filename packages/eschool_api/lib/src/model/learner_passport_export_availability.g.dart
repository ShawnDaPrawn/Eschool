// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learner_passport_export_availability.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearnerPassportExportAvailability _$LearnerPassportExportAvailabilityFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('LearnerPassportExportAvailability', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['available', 'formats']);
  final val = LearnerPassportExportAvailability(
    available: $checkedConvert('available', (v) => v as bool),
    formats: $checkedConvert(
      'formats',
      (v) => (v as List<dynamic>)
          .map(
            (e) => $enumDecode(
              _$LearnerPassportExportAvailabilityFormatsEnumEnumMap,
              e,
              unknownValue: LearnerPassportExportAvailabilityFormatsEnum
                  .unknownDefaultOpenApi,
            ),
          )
          .toList(),
    ),
  );
  return val;
});

Map<String, dynamic> _$LearnerPassportExportAvailabilityToJson(
  LearnerPassportExportAvailability instance,
) => <String, dynamic>{
  'available': instance.available,
  'formats': instance.formats
      .map((e) => _$LearnerPassportExportAvailabilityFormatsEnumEnumMap[e]!)
      .toList(),
};

const _$LearnerPassportExportAvailabilityFormatsEnumEnumMap = {
  LearnerPassportExportAvailabilityFormatsEnum.json: 'json',
  LearnerPassportExportAvailabilityFormatsEnum.pdf: 'pdf',
  LearnerPassportExportAvailabilityFormatsEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
