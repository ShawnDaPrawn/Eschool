// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pool_invite_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PoolInviteInput _$PoolInviteInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'PoolInviteInput',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'kind',
        'first_name',
        'last_name',
        'email',
        'initial_password',
        'offers_career_talk',
        'offers_job_shadow',
      ],
    );
    final val = PoolInviteInput(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      kind: $checkedConvert(
        'kind',
        (v) => $enumDecode(
          _$PoolInviteInputKindEnumEnumMap,
          v,
          unknownValue: PoolInviteInputKindEnum.unknownDefaultOpenApi,
        ),
      ),
      learnerId: $checkedConvert('learner_id', (v) => (v as num?)?.toInt()),
      firstName: $checkedConvert('first_name', (v) => v as String),
      lastName: $checkedConvert('last_name', (v) => v as String),
      email: $checkedConvert('email', (v) => v as String),
      initialPassword: $checkedConvert('initial_password', (v) => v as String),
      occupation: $checkedConvert('occupation', (v) => v as String?),
      organisation: $checkedConvert('organisation', (v) => v as String?),
      offersCareerTalk: $checkedConvert('offers_career_talk', (v) => v as bool),
      offersJobShadow: $checkedConvert('offers_job_shadow', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'learnerId': 'learner_id',
    'firstName': 'first_name',
    'lastName': 'last_name',
    'initialPassword': 'initial_password',
    'offersCareerTalk': 'offers_career_talk',
    'offersJobShadow': 'offers_job_shadow',
  },
);

Map<String, dynamic> _$PoolInviteInputToJson(PoolInviteInput instance) =>
    <String, dynamic>{
      'operation_id': instance.operationId,
      'kind': _$PoolInviteInputKindEnumEnumMap[instance.kind]!,
      'learner_id': ?instance.learnerId,
      'first_name': instance.firstName,
      'last_name': instance.lastName,
      'email': instance.email,
      'initial_password': instance.initialPassword,
      'occupation': ?instance.occupation,
      'organisation': ?instance.organisation,
      'offers_career_talk': instance.offersCareerTalk,
      'offers_job_shadow': instance.offersJobShadow,
    };

const _$PoolInviteInputKindEnumEnumMap = {
  PoolInviteInputKindEnum.alumnus: 'alumnus',
  PoolInviteInputKindEnum.volunteer: 'volunteer',
  PoolInviteInputKindEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
