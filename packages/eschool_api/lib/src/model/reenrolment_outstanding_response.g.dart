// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reenrolment_outstanding_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReenrolmentOutstandingResponse _$ReenrolmentOutstandingResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ReenrolmentOutstandingResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ReenrolmentOutstandingResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => ReenrolmentOutstanding.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$ReenrolmentOutstandingResponseCodeEnumEnumMap,
        v,
        unknownValue:
            ReenrolmentOutstandingResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$ReenrolmentOutstandingResponseToJson(
  ReenrolmentOutstandingResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$ReenrolmentOutstandingResponseCodeEnumEnumMap[instance.code]!,
};

const _$ReenrolmentOutstandingResponseCodeEnumEnumMap = {
  ReenrolmentOutstandingResponseCodeEnum.number200: 200,
  ReenrolmentOutstandingResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
