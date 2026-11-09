// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learner_passport_transfer_results_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearnerPassportTransferResultsInner
_$LearnerPassportTransferResultsInnerFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'LearnerPassportTransferResultsInner',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'subject_name',
        'mode',
        'percentage_bp',
        'rating_level',
        'released_on',
      ],
    );
    final val = LearnerPassportTransferResultsInner(
      subjectName: $checkedConvert('subject_name', (v) => v as String),
      mode: $checkedConvert(
        'mode',
        (v) => $enumDecode(
          _$LearnerPassportTransferResultsInnerModeEnumEnumMap,
          v,
          unknownValue:
              LearnerPassportTransferResultsInnerModeEnum.unknownDefaultOpenApi,
        ),
      ),
      percentageBp: $checkedConvert(
        'percentage_bp',
        (v) => (v as num?)?.toInt(),
      ),
      ratingLevel: $checkedConvert('rating_level', (v) => (v as num?)?.toInt()),
      releasedOn: $checkedConvert(
        'released_on',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'subjectName': 'subject_name',
    'percentageBp': 'percentage_bp',
    'ratingLevel': 'rating_level',
    'releasedOn': 'released_on',
  },
);

Map<String, dynamic> _$LearnerPassportTransferResultsInnerToJson(
  LearnerPassportTransferResultsInner instance,
) => <String, dynamic>{
  'subject_name': instance.subjectName,
  'mode': _$LearnerPassportTransferResultsInnerModeEnumEnumMap[instance.mode]!,
  'percentage_bp': instance.percentageBp,
  'rating_level': instance.ratingLevel,
  'released_on': instance.releasedOn?.toIso8601String(),
};

const _$LearnerPassportTransferResultsInnerModeEnumEnumMap = {
  LearnerPassportTransferResultsInnerModeEnum.official: 'official',
  LearnerPassportTransferResultsInnerModeEnum.running: 'running',
  LearnerPassportTransferResultsInnerModeEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
