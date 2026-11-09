// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'bursary_application_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

BursaryApplicationResponse _$BursaryApplicationResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('BursaryApplicationResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = BursaryApplicationResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => BursaryApplication.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$BursaryApplicationResponseCodeEnumEnumMap,
        v,
        unknownValue: BursaryApplicationResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$BursaryApplicationResponseToJson(
  BursaryApplicationResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$BursaryApplicationResponseCodeEnumEnumMap[instance.code]!,
};

const _$BursaryApplicationResponseCodeEnumEnumMap = {
  BursaryApplicationResponseCodeEnum.number200: 200,
  BursaryApplicationResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
