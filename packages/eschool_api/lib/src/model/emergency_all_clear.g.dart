// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'emergency_all_clear.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

EmergencyAllClear _$EmergencyAllClearFromJson(Map<String, dynamic> json) =>
    $checkedCreate('EmergencyAllClear', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['operation_id', 'revision']);
      final val = EmergencyAllClear(
        operationId: $checkedConvert('operation_id', (v) => v as String),
        revision: $checkedConvert('revision', (v) => (v as num).toInt()),
        message: $checkedConvert('message', (v) => v as String?),
      );
      return val;
    }, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$EmergencyAllClearToJson(EmergencyAllClear instance) =>
    <String, dynamic>{
      'operation_id': instance.operationId,
      'revision': instance.revision,
      'message': ?instance.message,
    };
