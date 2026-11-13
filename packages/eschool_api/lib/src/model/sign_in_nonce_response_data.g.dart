// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'sign_in_nonce_response_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SignInNonceResponseData _$SignInNonceResponseDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SignInNonceResponseData', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['nonce', 'expires_at', 'client_id']);
  final val = SignInNonceResponseData(
    nonce: $checkedConvert('nonce', (v) => v as String),
    expiresAt: $checkedConvert(
      'expires_at',
      (v) => DateTime.parse(v as String),
    ),
    clientId: $checkedConvert('client_id', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'expiresAt': 'expires_at', 'clientId': 'client_id'});

Map<String, dynamic> _$SignInNonceResponseDataToJson(
  SignInNonceResponseData instance,
) => <String, dynamic>{
  'nonce': instance.nonce,
  'expires_at': instance.expiresAt.toIso8601String(),
  'client_id': instance.clientId,
};
