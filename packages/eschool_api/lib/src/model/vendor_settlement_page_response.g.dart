// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'vendor_settlement_page_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

VendorSettlementPageResponse _$VendorSettlementPageResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('VendorSettlementPageResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = VendorSettlementPageResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => VendorSettlementPage.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$VendorSettlementPageResponseToJson(
  VendorSettlementPageResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
