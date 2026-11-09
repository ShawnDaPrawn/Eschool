// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pool_member_detail.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PoolMemberDetail _$PoolMemberDetailFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'PoolMemberDetail',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'kind',
        'learner_id',
        'name',
        'occupation',
        'organisation',
        'offers_career_talk',
        'offers_job_shadow',
        'consent_status',
        'consented_at',
        'withdrawn_at',
        'vetting_status',
        'vetted_on',
        'vetting_expires_on',
        'bookable_today',
        'revision',
        'vettings',
        'bookings',
      ],
    );
    final val = PoolMemberDetail(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      kind: $checkedConvert(
        'kind',
        (v) => $enumDecode(
          _$PoolMemberDetailKindEnumEnumMap,
          v,
          unknownValue: PoolMemberDetailKindEnum.unknownDefaultOpenApi,
        ),
      ),
      learnerId: $checkedConvert('learner_id', (v) => (v as num?)?.toInt()),
      name: $checkedConvert('name', (v) => v as String),
      occupation: $checkedConvert('occupation', (v) => v as String?),
      organisation: $checkedConvert('organisation', (v) => v as String?),
      offersCareerTalk: $checkedConvert('offers_career_talk', (v) => v as bool),
      offersJobShadow: $checkedConvert('offers_job_shadow', (v) => v as bool),
      consentStatus: $checkedConvert(
        'consent_status',
        (v) => $enumDecode(
          _$PoolMemberDetailConsentStatusEnumEnumMap,
          v,
          unknownValue: PoolMemberDetailConsentStatusEnum.unknownDefaultOpenApi,
        ),
      ),
      consentedAt: $checkedConvert('consented_at', (v) => v as String?),
      withdrawnAt: $checkedConvert('withdrawn_at', (v) => v as String?),
      vettingStatus: $checkedConvert(
        'vetting_status',
        (v) => $enumDecode(
          _$PoolMemberDetailVettingStatusEnumEnumMap,
          v,
          unknownValue: PoolMemberDetailVettingStatusEnum.unknownDefaultOpenApi,
        ),
      ),
      vettedOn: $checkedConvert('vetted_on', (v) => v as String?),
      vettingExpiresOn: $checkedConvert(
        'vetting_expires_on',
        (v) => v as String?,
      ),
      bookableToday: $checkedConvert('bookable_today', (v) => v as bool),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      vettings: $checkedConvert(
        'vettings',
        (v) => (v as List<dynamic>)
            .map((e) => PoolVettingRecord.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      bookings: $checkedConvert(
        'bookings',
        (v) => (v as List<dynamic>)
            .map((e) => PoolBookingRecord.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'offersCareerTalk': 'offers_career_talk',
    'offersJobShadow': 'offers_job_shadow',
    'consentStatus': 'consent_status',
    'consentedAt': 'consented_at',
    'withdrawnAt': 'withdrawn_at',
    'vettingStatus': 'vetting_status',
    'vettedOn': 'vetted_on',
    'vettingExpiresOn': 'vetting_expires_on',
    'bookableToday': 'bookable_today',
  },
);

Map<String, dynamic> _$PoolMemberDetailToJson(PoolMemberDetail instance) =>
    <String, dynamic>{
      'id': instance.id,
      'kind': _$PoolMemberDetailKindEnumEnumMap[instance.kind]!,
      'learner_id': instance.learnerId,
      'name': instance.name,
      'occupation': instance.occupation,
      'organisation': instance.organisation,
      'offers_career_talk': instance.offersCareerTalk,
      'offers_job_shadow': instance.offersJobShadow,
      'consent_status':
          _$PoolMemberDetailConsentStatusEnumEnumMap[instance.consentStatus]!,
      'consented_at': instance.consentedAt,
      'withdrawn_at': instance.withdrawnAt,
      'vetting_status':
          _$PoolMemberDetailVettingStatusEnumEnumMap[instance.vettingStatus]!,
      'vetted_on': instance.vettedOn,
      'vetting_expires_on': instance.vettingExpiresOn,
      'bookable_today': instance.bookableToday,
      'revision': instance.revision,
      'vettings': instance.vettings.map((e) => e.toJson()).toList(),
      'bookings': instance.bookings.map((e) => e.toJson()).toList(),
    };

const _$PoolMemberDetailKindEnumEnumMap = {
  PoolMemberDetailKindEnum.alumnus: 'alumnus',
  PoolMemberDetailKindEnum.volunteer: 'volunteer',
  PoolMemberDetailKindEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$PoolMemberDetailConsentStatusEnumEnumMap = {
  PoolMemberDetailConsentStatusEnum.invited: 'invited',
  PoolMemberDetailConsentStatusEnum.given: 'given',
  PoolMemberDetailConsentStatusEnum.withdrawn: 'withdrawn',
  PoolMemberDetailConsentStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$PoolMemberDetailVettingStatusEnumEnumMap = {
  PoolMemberDetailVettingStatusEnum.pending: 'pending',
  PoolMemberDetailVettingStatusEnum.cleared: 'cleared',
  PoolMemberDetailVettingStatusEnum.refused: 'refused',
  PoolMemberDetailVettingStatusEnum.revoked: 'revoked',
  PoolMemberDetailVettingStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
