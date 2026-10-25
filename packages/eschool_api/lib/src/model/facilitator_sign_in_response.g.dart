// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'facilitator_sign_in_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FacilitatorSignInResponse _$FacilitatorSignInResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('FacilitatorSignInResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = FacilitatorSignInResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => FacilitatorSignInData.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => v as String?),
  );
  return val;
});

Map<String, dynamic> _$FacilitatorSignInResponseToJson(
  FacilitatorSignInResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
