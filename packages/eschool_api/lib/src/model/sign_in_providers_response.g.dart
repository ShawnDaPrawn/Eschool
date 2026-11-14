// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'sign_in_providers_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SignInProvidersResponse _$SignInProvidersResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SignInProvidersResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = SignInProvidersResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => SignInProvidersResponseData.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => _onlyNullFromJson(v)),
  );
  return val;
});

Map<String, dynamic> _$SignInProvidersResponseToJson(
  SignInProvidersResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _onlyNullToJson(instance.code),
};
