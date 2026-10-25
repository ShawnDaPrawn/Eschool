// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'secure_card_qr_scan.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SecureCardQrScan _$SecureCardQrScanFromJson(Map<String, dynamic> json) =>
    $checkedCreate('SecureCardQrScan', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['operation_id', 'credential']);
      final val = SecureCardQrScan(
        operationId: $checkedConvert('operation_id', (v) => v as String),
        credential: $checkedConvert('credential', (v) => v as String),
      );
      return val;
    }, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$SecureCardQrScanToJson(SecureCardQrScan instance) =>
    <String, dynamic>{
      'operation_id': instance.operationId,
      'credential': instance.credential,
    };
