// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'task_insight_detail_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TaskInsightDetailResponse _$TaskInsightDetailResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TaskInsightDetailResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = TaskInsightDetailResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => TaskInsightDetail.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$TaskInsightDetailResponseCodeEnumEnumMap,
        v,
        unknownValue: TaskInsightDetailResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$TaskInsightDetailResponseToJson(
  TaskInsightDetailResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$TaskInsightDetailResponseCodeEnumEnumMap[instance.code]!,
};

const _$TaskInsightDetailResponseCodeEnumEnumMap = {
  TaskInsightDetailResponseCodeEnum.number200: 200,
  TaskInsightDetailResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
