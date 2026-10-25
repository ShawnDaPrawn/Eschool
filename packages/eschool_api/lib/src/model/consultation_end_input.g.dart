// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'consultation_end_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ConsultationEndInput _$ConsultationEndInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ConsultationEndInput', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['operation_id', 'revision', 'reason']);
  final val = ConsultationEndInput(
    operationId: $checkedConvert('operation_id', (v) => v as String),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    reason: $checkedConvert('reason', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$ConsultationEndInputToJson(
  ConsultationEndInput instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'revision': instance.revision,
  'reason': instance.reason,
};
