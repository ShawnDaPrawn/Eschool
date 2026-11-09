// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learning_path_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearningPathResponse _$LearningPathResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('LearningPathResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = LearningPathResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => LearningPath.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$LearningPathResponseToJson(
  LearningPathResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
