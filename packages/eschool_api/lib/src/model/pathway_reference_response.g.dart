// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pathway_reference_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PathwayReferenceResponse _$PathwayReferenceResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('PathwayReferenceResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = PathwayReferenceResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => PathwayReferenceResult.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$PathwayReferenceResponseToJson(
  PathwayReferenceResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
