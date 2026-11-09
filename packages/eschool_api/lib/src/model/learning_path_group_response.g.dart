// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learning_path_group_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearningPathGroupResponse _$LearningPathGroupResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('LearningPathGroupResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = LearningPathGroupResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => LearningPathGroup.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$LearningPathGroupResponseToJson(
  LearningPathGroupResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
