// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'recognition_versioned_correction.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RecognitionVersionedCorrection _$RecognitionVersionedCorrectionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('RecognitionVersionedCorrection', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['operation_id', 'revision', 'reason']);
  final val = RecognitionVersionedCorrection(
    operationId: $checkedConvert('operation_id', (v) => v as String),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    reason: $checkedConvert('reason', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$RecognitionVersionedCorrectionToJson(
  RecognitionVersionedCorrection instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'revision': instance.revision,
  'reason': instance.reason,
};
