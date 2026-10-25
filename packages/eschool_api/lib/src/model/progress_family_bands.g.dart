// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'progress_family_bands.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ProgressFamilyBands _$ProgressFamilyBandsFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ProgressFamilyBands', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['audience', 'learner_id', 'results']);
  final val = ProgressFamilyBands(
    audience: $checkedConvert(
      'audience',
      (v) => $enumDecode(
        _$ProgressFamilyBandsAudienceEnumEnumMap,
        v,
        unknownValue: ProgressFamilyBandsAudienceEnum.unknownDefaultOpenApi,
      ),
    ),
    learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
    results: $checkedConvert(
      'results',
      (v) => (v as List<dynamic>)
          .map((e) => ProgressReleasedBand.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
  );
  return val;
}, fieldKeyMap: const {'learnerId': 'learner_id'});

Map<String, dynamic> _$ProgressFamilyBandsToJson(
  ProgressFamilyBands instance,
) => <String, dynamic>{
  'audience': _$ProgressFamilyBandsAudienceEnumEnumMap[instance.audience]!,
  'learner_id': instance.learnerId,
  'results': instance.results.map((e) => e.toJson()).toList(),
};

const _$ProgressFamilyBandsAudienceEnumEnumMap = {
  ProgressFamilyBandsAudienceEnum.family: 'family',
  ProgressFamilyBandsAudienceEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
