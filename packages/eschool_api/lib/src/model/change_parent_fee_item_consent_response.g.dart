// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'change_parent_fee_item_consent_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ChangeParentFeeItemConsentResponse _$ChangeParentFeeItemConsentResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ChangeParentFeeItemConsentResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ChangeParentFeeItemConsentResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => ParentFeeItemConsentReceipt.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$ChangeParentFeeItemConsentResponseToJson(
  ChangeParentFeeItemConsentResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
