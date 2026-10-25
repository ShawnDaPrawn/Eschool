// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'consultation_revision_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ConsultationRevisionInput _$ConsultationRevisionInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ConsultationRevisionInput', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['operation_id', 'revision']);
  final val = ConsultationRevisionInput(
    operationId: $checkedConvert('operation_id', (v) => v as String),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$ConsultationRevisionInputToJson(
  ConsultationRevisionInput instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'revision': instance.revision,
};
