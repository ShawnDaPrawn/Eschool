// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_release_commit_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReleaseCommitWrite _$AcademicReleaseCommitWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicReleaseCommitWrite', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['term_id', 'mode', 'learner_ids', 'fingerprint'],
  );
  final val = AcademicReleaseCommitWrite(
    termId: $checkedConvert('term_id', (v) => (v as num?)?.toInt()),
    mode: $checkedConvert(
      'mode',
      (v) => $enumDecode(
        _$AcademicReleaseCommitWriteModeEnumEnumMap,
        v,
        unknownValue: AcademicReleaseCommitWriteModeEnum.unknownDefaultOpenApi,
      ),
    ),
    learnerIds: $checkedConvert(
      'learner_ids',
      (v) => (v as List<dynamic>).map((e) => (e as num).toInt()).toSet(),
    ),
    fingerprint: $checkedConvert('fingerprint', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'termId': 'term_id', 'learnerIds': 'learner_ids'});

Map<String, dynamic> _$AcademicReleaseCommitWriteToJson(
  AcademicReleaseCommitWrite instance,
) => <String, dynamic>{
  'term_id': instance.termId,
  'mode': _$AcademicReleaseCommitWriteModeEnumEnumMap[instance.mode]!,
  'learner_ids': instance.learnerIds.toList(),
  'fingerprint': instance.fingerprint,
};

const _$AcademicReleaseCommitWriteModeEnumEnumMap = {
  AcademicReleaseCommitWriteModeEnum.official: 'official',
  AcademicReleaseCommitWriteModeEnum.running: 'running',
  AcademicReleaseCommitWriteModeEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
