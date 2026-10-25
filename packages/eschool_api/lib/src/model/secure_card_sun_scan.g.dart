// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'secure_card_sun_scan.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SecureCardSunScan _$SecureCardSunScanFromJson(Map<String, dynamic> json) =>
    $checkedCreate('SecureCardSunScan', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['operation_id', 'card_id', 'picc', 'mac'],
      );
      final val = SecureCardSunScan(
        operationId: $checkedConvert('operation_id', (v) => v as String),
        cardId: $checkedConvert('card_id', (v) => v as String),
        picc: $checkedConvert('picc', (v) => v as String),
        mac: $checkedConvert('mac', (v) => v as String),
      );
      return val;
    }, fieldKeyMap: const {'operationId': 'operation_id', 'cardId': 'card_id'});

Map<String, dynamic> _$SecureCardSunScanToJson(SecureCardSunScan instance) =>
    <String, dynamic>{
      'operation_id': instance.operationId,
      'card_id': instance.cardId,
      'picc': instance.picc,
      'mac': instance.mac,
    };
