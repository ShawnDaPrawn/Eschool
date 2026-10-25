// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'secure_card_page_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SecureCardPageResponse _$SecureCardPageResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SecureCardPageResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = SecureCardPageResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => SecureCardPage.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$SecureCardPageResponseCodeEnumEnumMap,
        v,
        unknownValue: SecureCardPageResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$SecureCardPageResponseToJson(
  SecureCardPageResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$SecureCardPageResponseCodeEnumEnumMap[instance.code]!,
};

const _$SecureCardPageResponseCodeEnumEnumMap = {
  SecureCardPageResponseCodeEnum.number200: 200,
  SecureCardPageResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
