// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pool_profile.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PoolProfile _$PoolProfileFromJson(Map<String, dynamic> json) => $checkedCreate(
  'PoolProfile',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'user_id',
        'name',
        'school',
        'pool_member_id',
        'kind',
        'offers_career_talk',
        'offers_job_shadow',
        'consented_at',
        'vetting_status',
        'vetting_expires_on',
        'vetted',
      ],
    );
    final val = PoolProfile(
      userId: $checkedConvert('user_id', (v) => (v as num).toInt()),
      name: $checkedConvert('name', (v) => v as String),
      school: $checkedConvert(
        'school',
        (v) => PoolProfileSchool.fromJson(v as Map<String, dynamic>),
      ),
      poolMemberId: $checkedConvert(
        'pool_member_id',
        (v) => (v as num).toInt(),
      ),
      kind: $checkedConvert(
        'kind',
        (v) => $enumDecode(
          _$PoolProfileKindEnumEnumMap,
          v,
          unknownValue: PoolProfileKindEnum.unknownDefaultOpenApi,
        ),
      ),
      offersCareerTalk: $checkedConvert('offers_career_talk', (v) => v as bool),
      offersJobShadow: $checkedConvert('offers_job_shadow', (v) => v as bool),
      consentedAt: $checkedConvert('consented_at', (v) => v as String?),
      vettingStatus: $checkedConvert('vetting_status', (v) => v as String),
      vettingExpiresOn: $checkedConvert(
        'vetting_expires_on',
        (v) => v as String?,
      ),
      vetted: $checkedConvert('vetted', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'userId': 'user_id',
    'poolMemberId': 'pool_member_id',
    'offersCareerTalk': 'offers_career_talk',
    'offersJobShadow': 'offers_job_shadow',
    'consentedAt': 'consented_at',
    'vettingStatus': 'vetting_status',
    'vettingExpiresOn': 'vetting_expires_on',
  },
);

Map<String, dynamic> _$PoolProfileToJson(PoolProfile instance) =>
    <String, dynamic>{
      'user_id': instance.userId,
      'name': instance.name,
      'school': instance.school.toJson(),
      'pool_member_id': instance.poolMemberId,
      'kind': _$PoolProfileKindEnumEnumMap[instance.kind]!,
      'offers_career_talk': instance.offersCareerTalk,
      'offers_job_shadow': instance.offersJobShadow,
      'consented_at': instance.consentedAt,
      'vetting_status': instance.vettingStatus,
      'vetting_expires_on': instance.vettingExpiresOn,
      'vetted': instance.vetted,
    };

const _$PoolProfileKindEnumEnumMap = {
  PoolProfileKindEnum.alumnus: 'alumnus',
  PoolProfileKindEnum.volunteer: 'volunteer',
  PoolProfileKindEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
