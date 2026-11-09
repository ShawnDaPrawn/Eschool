// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'bursary_queue_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

BursaryQueueResponse _$BursaryQueueResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('BursaryQueueResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = BursaryQueueResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => BursaryQueue.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$BursaryQueueResponseCodeEnumEnumMap,
        v,
        unknownValue: BursaryQueueResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$BursaryQueueResponseToJson(
  BursaryQueueResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$BursaryQueueResponseCodeEnumEnumMap[instance.code]!,
};

const _$BursaryQueueResponseCodeEnumEnumMap = {
  BursaryQueueResponseCodeEnum.number200: 200,
  BursaryQueueResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
