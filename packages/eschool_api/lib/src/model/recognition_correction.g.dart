// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'recognition_correction.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RecognitionCorrection _$RecognitionCorrectionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('RecognitionCorrection', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['operation_id', 'reason']);
  final val = RecognitionCorrection(
    operationId: $checkedConvert('operation_id', (v) => v as String),
    reason: $checkedConvert('reason', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$RecognitionCorrectionToJson(
  RecognitionCorrection instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'reason': instance.reason,
};
