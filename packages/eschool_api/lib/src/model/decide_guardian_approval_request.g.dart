// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'decide_guardian_approval_request.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DecideGuardianApprovalRequest _$DecideGuardianApprovalRequestFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('DecideGuardianApprovalRequest', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['decision', 'consent_version']);
  final val = DecideGuardianApprovalRequest(
    decision: $checkedConvert(
      'decision',
      (v) => $enumDecode(
        _$DecideGuardianApprovalRequestDecisionEnumEnumMap,
        v,
        unknownValue:
            DecideGuardianApprovalRequestDecisionEnum.unknownDefaultOpenApi,
      ),
    ),
    consentVersion: $checkedConvert('consent_version', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'consentVersion': 'consent_version'});

Map<String, dynamic> _$DecideGuardianApprovalRequestToJson(
  DecideGuardianApprovalRequest instance,
) => <String, dynamic>{
  'decision':
      _$DecideGuardianApprovalRequestDecisionEnumEnumMap[instance.decision]!,
  'consent_version': instance.consentVersion,
};

const _$DecideGuardianApprovalRequestDecisionEnumEnumMap = {
  DecideGuardianApprovalRequestDecisionEnum.approve: 'approve',
  DecideGuardianApprovalRequestDecisionEnum.withdraw: 'withdraw',
  DecideGuardianApprovalRequestDecisionEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
