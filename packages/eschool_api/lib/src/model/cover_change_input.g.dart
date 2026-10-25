// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'cover_change_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CoverChangeInput _$CoverChangeInputFromJson(Map<String, dynamic> json) =>
    $checkedCreate('CoverChangeInput', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['operation_id', 'revision', 'reason'],
      );
      final val = CoverChangeInput(
        operationId: $checkedConvert('operation_id', (v) => v as String),
        revision: $checkedConvert('revision', (v) => (v as num).toInt()),
        reason: $checkedConvert('reason', (v) => v as String),
      );
      return val;
    }, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$CoverChangeInputToJson(CoverChangeInput instance) =>
    <String, dynamic>{
      'operation_id': instance.operationId,
      'revision': instance.revision,
      'reason': instance.reason,
    };
