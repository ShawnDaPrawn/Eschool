// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'close_giving_campaign_request.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CloseGivingCampaignRequest _$CloseGivingCampaignRequestFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CloseGivingCampaignRequest', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['operation_id', 'revision']);
  final val = CloseGivingCampaignRequest(
    operationId: $checkedConvert('operation_id', (v) => v as String),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$CloseGivingCampaignRequestToJson(
  CloseGivingCampaignRequest instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'revision': instance.revision,
};
