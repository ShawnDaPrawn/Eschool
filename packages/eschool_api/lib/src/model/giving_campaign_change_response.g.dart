// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'giving_campaign_change_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GivingCampaignChangeResponse _$GivingCampaignChangeResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GivingCampaignChangeResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = GivingCampaignChangeResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => GivingCampaignChange.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$GivingCampaignChangeResponseCodeEnumEnumMap,
        v,
        unknownValue:
            GivingCampaignChangeResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$GivingCampaignChangeResponseToJson(
  GivingCampaignChangeResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$GivingCampaignChangeResponseCodeEnumEnumMap[instance.code]!,
};

const _$GivingCampaignChangeResponseCodeEnumEnumMap = {
  GivingCampaignChangeResponseCodeEnum.number200: 200,
  GivingCampaignChangeResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
