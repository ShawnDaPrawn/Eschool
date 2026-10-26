// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'secure_card_reason.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SecureCardReason _$SecureCardReasonFromJson(Map<String, dynamic> json) =>
    $checkedCreate('SecureCardReason', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['operation_id', 'reason']);
      final val = SecureCardReason(
        operationId: $checkedConvert('operation_id', (v) => v as String),
        reason: $checkedConvert('reason', (v) => v as String),
      );
      return val;
    }, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$SecureCardReasonToJson(SecureCardReason instance) =>
    <String, dynamic>{
      'operation_id': instance.operationId,
      'reason': instance.reason,
    };
