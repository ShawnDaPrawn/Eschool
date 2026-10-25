// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'secure_card_configuration_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SecureCardConfigurationResponse _$SecureCardConfigurationResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SecureCardConfigurationResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = SecureCardConfigurationResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => SecureCardConfiguration.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$SecureCardConfigurationResponseCodeEnumEnumMap,
        v,
        unknownValue:
            SecureCardConfigurationResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$SecureCardConfigurationResponseToJson(
  SecureCardConfigurationResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$SecureCardConfigurationResponseCodeEnumEnumMap[instance.code]!,
};

const _$SecureCardConfigurationResponseCodeEnumEnumMap = {
  SecureCardConfigurationResponseCodeEnum.number200: 200,
  SecureCardConfigurationResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
