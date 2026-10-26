// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'secure_card_credential_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SecureCardCredentialResponse _$SecureCardCredentialResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SecureCardCredentialResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = SecureCardCredentialResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => SecureCardCredential.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$SecureCardCredentialResponseCodeEnumEnumMap,
        v,
        unknownValue:
            SecureCardCredentialResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$SecureCardCredentialResponseToJson(
  SecureCardCredentialResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$SecureCardCredentialResponseCodeEnumEnumMap[instance.code]!,
};

const _$SecureCardCredentialResponseCodeEnumEnumMap = {
  SecureCardCredentialResponseCodeEnum.number200: 200,
  SecureCardCredentialResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
