// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pathway_projection_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PathwayProjectionResponse _$PathwayProjectionResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('PathwayProjectionResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = PathwayProjectionResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => PathwayProjection.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$PathwayProjectionResponseToJson(
  PathwayProjectionResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
