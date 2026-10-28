// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'vendor_operator_sign_in_data_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

VendorOperatorSignInDataResponse _$VendorOperatorSignInDataResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('VendorOperatorSignInDataResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = VendorOperatorSignInDataResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => VendorOperatorSignInData.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => v as String?),
  );
  return val;
});

Map<String, dynamic> _$VendorOperatorSignInDataResponseToJson(
  VendorOperatorSignInDataResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
