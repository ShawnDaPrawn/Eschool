// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'vendor_operator_profile_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

VendorOperatorProfileResponse _$VendorOperatorProfileResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('VendorOperatorProfileResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = VendorOperatorProfileResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => VendorOperatorProfile.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => v as String?),
  );
  return val;
});

Map<String, dynamic> _$VendorOperatorProfileResponseToJson(
  VendorOperatorProfileResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
