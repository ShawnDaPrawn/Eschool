// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pool_member_summary.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PoolMemberSummary _$PoolMemberSummaryFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'PoolMemberSummary',
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
      ],
    );
    final val = PoolMemberSummary(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      kind: $checkedConvert(
        'kind',
        (v) => $enumDecode(
          _$PoolMemberSummaryKindEnumEnumMap,
          v,
          unknownValue: PoolMemberSummaryKindEnum.unknownDefaultOpenApi,
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
          _$PoolMemberSummaryConsentStatusEnumEnumMap,
          v,
          unknownValue:
              PoolMemberSummaryConsentStatusEnum.unknownDefaultOpenApi,
        ),
      ),
      consentedAt: $checkedConvert('consented_at', (v) => v as String?),
      withdrawnAt: $checkedConvert('withdrawn_at', (v) => v as String?),
      vettingStatus: $checkedConvert(
        'vetting_status',
        (v) => $enumDecode(
          _$PoolMemberSummaryVettingStatusEnumEnumMap,
          v,
          unknownValue:
              PoolMemberSummaryVettingStatusEnum.unknownDefaultOpenApi,
        ),
      ),
      vettedOn: $checkedConvert('vetted_on', (v) => v as String?),
      vettingExpiresOn: $checkedConvert(
        'vetting_expires_on',
        (v) => v as String?,
      ),
      bookableToday: $checkedConvert('bookable_today', (v) => v as bool),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
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

Map<String, dynamic> _$PoolMemberSummaryToJson(PoolMemberSummary instance) =>
    <String, dynamic>{
      'id': instance.id,
      'kind': _$PoolMemberSummaryKindEnumEnumMap[instance.kind]!,
      'learner_id': instance.learnerId,
      'name': instance.name,
      'occupation': instance.occupation,
      'organisation': instance.organisation,
      'offers_career_talk': instance.offersCareerTalk,
      'offers_job_shadow': instance.offersJobShadow,
      'consent_status':
          _$PoolMemberSummaryConsentStatusEnumEnumMap[instance.consentStatus]!,
      'consented_at': instance.consentedAt,
      'withdrawn_at': instance.withdrawnAt,
      'vetting_status':
          _$PoolMemberSummaryVettingStatusEnumEnumMap[instance.vettingStatus]!,
      'vetted_on': instance.vettedOn,
      'vetting_expires_on': instance.vettingExpiresOn,
      'bookable_today': instance.bookableToday,
      'revision': instance.revision,
    };

const _$PoolMemberSummaryKindEnumEnumMap = {
  PoolMemberSummaryKindEnum.alumnus: 'alumnus',
  PoolMemberSummaryKindEnum.volunteer: 'volunteer',
  PoolMemberSummaryKindEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$PoolMemberSummaryConsentStatusEnumEnumMap = {
  PoolMemberSummaryConsentStatusEnum.invited: 'invited',
  PoolMemberSummaryConsentStatusEnum.given: 'given',
  PoolMemberSummaryConsentStatusEnum.withdrawn: 'withdrawn',
  PoolMemberSummaryConsentStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$PoolMemberSummaryVettingStatusEnumEnumMap = {
  PoolMemberSummaryVettingStatusEnum.pending: 'pending',
  PoolMemberSummaryVettingStatusEnum.cleared: 'cleared',
  PoolMemberSummaryVettingStatusEnum.refused: 'refused',
  PoolMemberSummaryVettingStatusEnum.revoked: 'revoked',
  PoolMemberSummaryVettingStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
