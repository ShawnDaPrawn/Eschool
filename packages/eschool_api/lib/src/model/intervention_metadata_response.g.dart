// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'intervention_metadata_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

InterventionMetadataResponse _$InterventionMetadataResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('InterventionMetadataResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = InterventionMetadataResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => InterventionMetadata.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$InterventionMetadataResponseToJson(
  InterventionMetadataResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
