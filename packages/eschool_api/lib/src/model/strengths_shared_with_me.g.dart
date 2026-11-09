// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'strengths_shared_with_me.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

StrengthsSharedWithMe _$StrengthsSharedWithMeFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'StrengthsSharedWithMe',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'learner_id',
        'learner_name',
        'kind',
        'shared_on',
        'expires_at',
      ],
    );
    final val = StrengthsSharedWithMe(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      learnerName: $checkedConvert('learner_name', (v) => v as String),
      kind: $checkedConvert(
        'kind',
        (v) => $enumDecode(
          _$StrengthsSharedWithMeKindEnumEnumMap,
          v,
          unknownValue: StrengthsSharedWithMeKindEnum.unknownDefaultOpenApi,
        ),
      ),
      sharedOn: $checkedConvert(
        'shared_on',
        (v) => DateTime.parse(v as String),
      ),
      expiresAt: $checkedConvert(
        'expires_at',
        (v) => DateTime.parse(v as String),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'learnerName': 'learner_name',
    'sharedOn': 'shared_on',
    'expiresAt': 'expires_at',
  },
);

Map<String, dynamic> _$StrengthsSharedWithMeToJson(
  StrengthsSharedWithMe instance,
) => <String, dynamic>{
  'id': instance.id,
  'learner_id': instance.learnerId,
  'learner_name': instance.learnerName,
  'kind': _$StrengthsSharedWithMeKindEnumEnumMap[instance.kind]!,
  'shared_on': instance.sharedOn.toIso8601String(),
  'expires_at': instance.expiresAt.toIso8601String(),
};

const _$StrengthsSharedWithMeKindEnumEnumMap = {
  StrengthsSharedWithMeKindEnum.guardian: 'guardian',
  StrengthsSharedWithMeKindEnum.teacher: 'teacher',
  StrengthsSharedWithMeKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
