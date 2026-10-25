// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'support_reason_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SupportReasonInput _$SupportReasonInputFromJson(Map<String, dynamic> json) =>
    $checkedCreate('SupportReasonInput', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['operation_id', 'revision', 'reason'],
      );
      final val = SupportReasonInput(
        operationId: $checkedConvert('operation_id', (v) => v as String),
        revision: $checkedConvert('revision', (v) => (v as num).toInt()),
        reason: $checkedConvert('reason', (v) => v as String),
      );
      return val;
    }, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$SupportReasonInputToJson(SupportReasonInput instance) =>
    <String, dynamic>{
      'operation_id': instance.operationId,
      'revision': instance.revision,
      'reason': instance.reason,
    };
