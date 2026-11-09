// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'giving_campaign_summary_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GivingCampaignSummaryResponse _$GivingCampaignSummaryResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GivingCampaignSummaryResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = GivingCampaignSummaryResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => GivingCampaignSummary.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$GivingCampaignSummaryResponseCodeEnumEnumMap,
        v,
        unknownValue:
            GivingCampaignSummaryResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$GivingCampaignSummaryResponseToJson(
  GivingCampaignSummaryResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$GivingCampaignSummaryResponseCodeEnumEnumMap[instance.code]!,
};

const _$GivingCampaignSummaryResponseCodeEnumEnumMap = {
  GivingCampaignSummaryResponseCodeEnum.number200: 200,
  GivingCampaignSummaryResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
