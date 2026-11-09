// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pool_withdrawal.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PoolWithdrawal _$PoolWithdrawalFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'PoolWithdrawal',
  json,
  ($checkedConvert) {
    $checkKeys(json, requiredKeys: const ['consent_status', 'withdrawn_at']);
    final val = PoolWithdrawal(
      consentStatus: $checkedConvert(
        'consent_status',
        (v) => $enumDecode(
          _$PoolWithdrawalConsentStatusEnumEnumMap,
          v,
          unknownValue: PoolWithdrawalConsentStatusEnum.unknownDefaultOpenApi,
        ),
      ),
      withdrawnAt: $checkedConvert('withdrawn_at', (v) => v as String?),
    );
    return val;
  },
  fieldKeyMap: const {
    'consentStatus': 'consent_status',
    'withdrawnAt': 'withdrawn_at',
  },
);

Map<String, dynamic> _$PoolWithdrawalToJson(PoolWithdrawal instance) =>
    <String, dynamic>{
      'consent_status':
          _$PoolWithdrawalConsentStatusEnumEnumMap[instance.consentStatus]!,
      'withdrawn_at': instance.withdrawnAt,
    };

const _$PoolWithdrawalConsentStatusEnumEnumMap = {
  PoolWithdrawalConsentStatusEnum.withdrawn: 'withdrawn',
  PoolWithdrawalConsentStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
