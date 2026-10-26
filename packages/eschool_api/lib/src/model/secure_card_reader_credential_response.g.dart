// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'secure_card_reader_credential_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SecureCardReaderCredentialResponse _$SecureCardReaderCredentialResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SecureCardReaderCredentialResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = SecureCardReaderCredentialResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => SecureCardReaderCredential.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$SecureCardReaderCredentialResponseCodeEnumEnumMap,
        v,
        unknownValue:
            SecureCardReaderCredentialResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$SecureCardReaderCredentialResponseToJson(
  SecureCardReaderCredentialResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$SecureCardReaderCredentialResponseCodeEnumEnumMap[instance.code]!,
};

const _$SecureCardReaderCredentialResponseCodeEnumEnumMap = {
  SecureCardReaderCredentialResponseCodeEnum.number200: 200,
  SecureCardReaderCredentialResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
