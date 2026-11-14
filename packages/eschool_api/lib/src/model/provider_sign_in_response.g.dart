// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'provider_sign_in_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ProviderSignInResponse _$ProviderSignInResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ProviderSignInResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ProviderSignInResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => ProviderSignInResponseData.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => _onlyNullFromJson(v)),
  );
  return val;
});

Map<String, dynamic> _$ProviderSignInResponseToJson(
  ProviderSignInResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _onlyNullToJson(instance.code),
};
