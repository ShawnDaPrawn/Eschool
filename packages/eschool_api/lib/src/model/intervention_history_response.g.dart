// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'intervention_history_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

InterventionHistoryResponse _$InterventionHistoryResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('InterventionHistoryResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = InterventionHistoryResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => InterventionHistory.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$InterventionHistoryResponseToJson(
  InterventionHistoryResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
