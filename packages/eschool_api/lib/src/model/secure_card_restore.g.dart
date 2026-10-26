// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'secure_card_restore.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SecureCardRestore _$SecureCardRestoreFromJson(Map<String, dynamic> json) =>
    $checkedCreate('SecureCardRestore', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['operation_id', 'reason']);
      final val = SecureCardRestore(
        operationId: $checkedConvert('operation_id', (v) => v as String),
        reason: $checkedConvert('reason', (v) => v as String),
        picc: $checkedConvert('picc', (v) => v as String?),
        mac: $checkedConvert('mac', (v) => v as String?),
      );
      return val;
    }, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$SecureCardRestoreToJson(SecureCardRestore instance) =>
    <String, dynamic>{
      'operation_id': instance.operationId,
      'reason': instance.reason,
      'picc': ?instance.picc,
      'mac': ?instance.mac,
    };
