// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'bursary_change_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

BursaryChangeResponse _$BursaryChangeResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('BursaryChangeResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = BursaryChangeResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => BursaryChange.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$BursaryChangeResponseCodeEnumEnumMap,
        v,
        unknownValue: BursaryChangeResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$BursaryChangeResponseToJson(
  BursaryChangeResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$BursaryChangeResponseCodeEnumEnumMap[instance.code]!,
};

const _$BursaryChangeResponseCodeEnumEnumMap = {
  BursaryChangeResponseCodeEnum.number200: 200,
  BursaryChangeResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
