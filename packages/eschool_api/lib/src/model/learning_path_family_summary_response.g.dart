// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learning_path_family_summary_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearningPathFamilySummaryResponse _$LearningPathFamilySummaryResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('LearningPathFamilySummaryResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = LearningPathFamilySummaryResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => LearningPathFamilySummary.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$LearningPathFamilySummaryResponseToJson(
  LearningPathFamilySummaryResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
