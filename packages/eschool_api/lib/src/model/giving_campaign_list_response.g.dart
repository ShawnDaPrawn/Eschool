// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'giving_campaign_list_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GivingCampaignListResponse _$GivingCampaignListResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GivingCampaignListResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = GivingCampaignListResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => GivingCampaignList.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$GivingCampaignListResponseCodeEnumEnumMap,
        v,
        unknownValue: GivingCampaignListResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$GivingCampaignListResponseToJson(
  GivingCampaignListResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$GivingCampaignListResponseCodeEnumEnumMap[instance.code]!,
};

const _$GivingCampaignListResponseCodeEnumEnumMap = {
  GivingCampaignListResponseCodeEnum.number200: 200,
  GivingCampaignListResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
