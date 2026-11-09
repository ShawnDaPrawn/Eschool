// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'publish_reenrolment_contract_request.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PublishReenrolmentContractRequest _$PublishReenrolmentContractRequestFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'PublishReenrolmentContractRequest',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'session_year_id',
        'title',
        'body',
        'acceptance_rule',
        'respond_by',
      ],
    );
    final val = PublishReenrolmentContractRequest(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      title: $checkedConvert('title', (v) => v as String),
      body: $checkedConvert('body', (v) => v as String),
      acceptanceRule: $checkedConvert(
        'acceptance_rule',
        (v) => $enumDecode(
          _$PublishReenrolmentContractRequestAcceptanceRuleEnumEnumMap,
          v,
          unknownValue: PublishReenrolmentContractRequestAcceptanceRuleEnum
              .unknownDefaultOpenApi,
        ),
      ),
      respondBy: $checkedConvert(
        'respond_by',
        (v) => DateTime.parse(v as String),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'sessionYearId': 'session_year_id',
    'acceptanceRule': 'acceptance_rule',
    'respondBy': 'respond_by',
  },
);

Map<String, dynamic> _$PublishReenrolmentContractRequestToJson(
  PublishReenrolmentContractRequest instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'session_year_id': instance.sessionYearId,
  'title': instance.title,
  'body': instance.body,
  'acceptance_rule':
      _$PublishReenrolmentContractRequestAcceptanceRuleEnumEnumMap[instance
          .acceptanceRule]!,
  'respond_by': instance.respondBy.toIso8601String(),
};

const _$PublishReenrolmentContractRequestAcceptanceRuleEnumEnumMap = {
  PublishReenrolmentContractRequestAcceptanceRuleEnum.allFeePayers:
      'all_fee_payers',
  PublishReenrolmentContractRequestAcceptanceRuleEnum.feePayer: 'fee_payer',
  PublishReenrolmentContractRequestAcceptanceRuleEnum.anyGuardian:
      'any_guardian',
  PublishReenrolmentContractRequestAcceptanceRuleEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
