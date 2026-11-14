// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'sign_in_nonce_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SignInNonceResponse _$SignInNonceResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('SignInNonceResponse', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'data', 'code'],
      );
      final val = SignInNonceResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        data: $checkedConvert(
          'data',
          (v) => SignInNonceResponseData.fromJson(v as Map<String, dynamic>),
        ),
        code: $checkedConvert('code', (v) => _onlyNullFromJson(v)),
      );
      return val;
    });

Map<String, dynamic> _$SignInNonceResponseToJson(
  SignInNonceResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _onlyNullToJson(instance.code),
};
