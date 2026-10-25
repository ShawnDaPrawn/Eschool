// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'intervention_queue_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

InterventionQueueResponse _$InterventionQueueResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('InterventionQueueResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = InterventionQueueResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => InterventionQueue.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$InterventionQueueResponseToJson(
  InterventionQueueResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
