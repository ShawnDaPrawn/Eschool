// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'vendor_operator_sign_out_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

VendorOperatorSignOutResponse _$VendorOperatorSignOutResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('VendorOperatorSignOutResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = VendorOperatorSignOutResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert('data', (v) => v),
    code: $checkedConvert('code', (v) => v as String?),
  );
  return val;
});

Map<String, dynamic> _$VendorOperatorSignOutResponseToJson(
  VendorOperatorSignOutResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data,
  'code': instance.code,
};
