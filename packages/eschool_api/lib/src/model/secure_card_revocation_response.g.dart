// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'secure_card_revocation_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SecureCardRevocationResponse _$SecureCardRevocationResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SecureCardRevocationResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = SecureCardRevocationResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => SecureCardRevocation.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$SecureCardRevocationResponseCodeEnumEnumMap,
        v,
        unknownValue:
            SecureCardRevocationResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$SecureCardRevocationResponseToJson(
  SecureCardRevocationResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$SecureCardRevocationResponseCodeEnumEnumMap[instance.code]!,
};

const _$SecureCardRevocationResponseCodeEnumEnumMap = {
  SecureCardRevocationResponseCodeEnum.number200: 200,
  SecureCardRevocationResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
