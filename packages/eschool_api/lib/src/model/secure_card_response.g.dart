// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'secure_card_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SecureCardResponse _$SecureCardResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('SecureCardResponse', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'data', 'code'],
      );
      final val = SecureCardResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        data: $checkedConvert(
          'data',
          (v) => SecureCard.fromJson(v as Map<String, dynamic>),
        ),
        code: $checkedConvert(
          'code',
          (v) => $enumDecode(
            _$SecureCardResponseCodeEnumEnumMap,
            v,
            unknownValue: SecureCardResponseCodeEnum.unknownDefaultOpenApi,
          ),
        ),
      );
      return val;
    });

Map<String, dynamic> _$SecureCardResponseToJson(SecureCardResponse instance) =>
    <String, dynamic>{
      'error': instance.error,
      'message': instance.message,
      'data': instance.data.toJson(),
      'code': _$SecureCardResponseCodeEnumEnumMap[instance.code]!,
    };

const _$SecureCardResponseCodeEnumEnumMap = {
  SecureCardResponseCodeEnum.number200: 200,
  SecureCardResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
