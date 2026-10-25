// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'facilitator_sign_out_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FacilitatorSignOutResponse _$FacilitatorSignOutResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('FacilitatorSignOutResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = FacilitatorSignOutResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert('data', (v) => v),
    code: $checkedConvert('code', (v) => v as String?),
  );
  return val;
});

Map<String, dynamic> _$FacilitatorSignOutResponseToJson(
  FacilitatorSignOutResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data,
  'code': instance.code,
};
