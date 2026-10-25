// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'secure_card_replacement_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SecureCardReplacementResponse _$SecureCardReplacementResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SecureCardReplacementResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = SecureCardReplacementResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => SecureCardReplacement.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$SecureCardReplacementResponseCodeEnumEnumMap,
        v,
        unknownValue:
            SecureCardReplacementResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$SecureCardReplacementResponseToJson(
  SecureCardReplacementResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$SecureCardReplacementResponseCodeEnumEnumMap[instance.code]!,
};

const _$SecureCardReplacementResponseCodeEnumEnumMap = {
  SecureCardReplacementResponseCodeEnum.number200: 200,
  SecureCardReplacementResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
