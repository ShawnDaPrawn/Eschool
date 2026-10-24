// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_release_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReleaseWrite _$AcademicReleaseWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicReleaseWrite', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['term_id', 'mode', 'learner_ids']);
  final val = AcademicReleaseWrite(
    termId: $checkedConvert('term_id', (v) => (v as num?)?.toInt()),
    mode: $checkedConvert(
      'mode',
      (v) => $enumDecode(
        _$AcademicReleaseWriteModeEnumEnumMap,
        v,
        unknownValue: AcademicReleaseWriteModeEnum.unknownDefaultOpenApi,
      ),
    ),
    learnerIds: $checkedConvert(
      'learner_ids',
      (v) => (v as List<dynamic>).map((e) => (e as num).toInt()).toSet(),
    ),
    fingerprint: $checkedConvert('fingerprint', (v) => v as String?),
  );
  return val;
}, fieldKeyMap: const {'termId': 'term_id', 'learnerIds': 'learner_ids'});

Map<String, dynamic> _$AcademicReleaseWriteToJson(
  AcademicReleaseWrite instance,
) => <String, dynamic>{
  'term_id': instance.termId,
  'mode': _$AcademicReleaseWriteModeEnumEnumMap[instance.mode]!,
  'learner_ids': instance.learnerIds.toList(),
  'fingerprint': ?instance.fingerprint,
};

const _$AcademicReleaseWriteModeEnumEnumMap = {
  AcademicReleaseWriteModeEnum.official: 'official',
  AcademicReleaseWriteModeEnum.running: 'running',
  AcademicReleaseWriteModeEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
