// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'vendor_counter_page_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

VendorCounterPageResponse _$VendorCounterPageResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('VendorCounterPageResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = VendorCounterPageResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => VendorCounterPage.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$VendorCounterPageResponseToJson(
  VendorCounterPageResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
