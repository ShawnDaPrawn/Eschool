// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'mastery_graph_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

MasteryGraphResponse _$MasteryGraphResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('MasteryGraphResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = MasteryGraphResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => MasteryGraph.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$MasteryGraphResponseToJson(
  MasteryGraphResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
