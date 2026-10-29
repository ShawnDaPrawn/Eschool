// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'commerce_vendor_page_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommerceVendorPageResponse _$CommerceVendorPageResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CommerceVendorPageResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = CommerceVendorPageResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => CommerceVendorPage.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$CommerceVendorPageResponseToJson(
  CommerceVendorPageResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
