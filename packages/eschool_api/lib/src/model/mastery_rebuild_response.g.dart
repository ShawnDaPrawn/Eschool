// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'mastery_rebuild_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

MasteryRebuildResponse _$MasteryRebuildResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('MasteryRebuildResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = MasteryRebuildResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => MasteryRebuild.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$MasteryRebuildResponseToJson(
  MasteryRebuildResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
