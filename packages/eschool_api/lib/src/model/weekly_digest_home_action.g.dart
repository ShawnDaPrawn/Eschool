// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'weekly_digest_home_action.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WeeklyDigestHomeAction _$WeeklyDigestHomeActionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WeeklyDigestHomeAction',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['kind', 'learner_id', 'label', 'content_version_id'],
    );
    final val = WeeklyDigestHomeAction(
      kind: $checkedConvert(
        'kind',
        (v) => $enumDecode(
          _$WeeklyDigestHomeActionKindEnumEnumMap,
          v,
          unknownValue: WeeklyDigestHomeActionKindEnum.unknownDefaultOpenApi,
        ),
      ),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      label: $checkedConvert('label', (v) => v as String),
      contentVersionId: $checkedConvert(
        'content_version_id',
        (v) => (v as num?)?.toInt(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'contentVersionId': 'content_version_id',
  },
);

Map<String, dynamic> _$WeeklyDigestHomeActionToJson(
  WeeklyDigestHomeAction instance,
) => <String, dynamic>{
  'kind': _$WeeklyDigestHomeActionKindEnumEnumMap[instance.kind]!,
  'learner_id': instance.learnerId,
  'label': instance.label,
  'content_version_id': instance.contentVersionId,
};

const _$WeeklyDigestHomeActionKindEnumEnumMap = {
  WeeklyDigestHomeActionKindEnum.practice: 'practice',
  WeeklyDigestHomeActionKindEnum.marks: 'marks',
  WeeklyDigestHomeActionKindEnum.attendance: 'attendance',
  WeeklyDigestHomeActionKindEnum.calendar: 'calendar',
  WeeklyDigestHomeActionKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
