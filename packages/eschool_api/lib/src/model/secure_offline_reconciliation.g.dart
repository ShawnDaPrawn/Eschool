// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'secure_offline_reconciliation.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SecureOfflineReconciliation _$SecureOfflineReconciliationFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'SecureOfflineReconciliation',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'manifest',
        'credential',
        'scanned_at',
      ],
    );
    final val = SecureOfflineReconciliation(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      manifest: $checkedConvert('manifest', (v) => v as String),
      credential: $checkedConvert('credential', (v) => v as String),
      scannedAt: $checkedConvert('scanned_at', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {'operationId': 'operation_id', 'scannedAt': 'scanned_at'},
);

Map<String, dynamic> _$SecureOfflineReconciliationToJson(
  SecureOfflineReconciliation instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'manifest': instance.manifest,
  'credential': instance.credential,
  'scanned_at': instance.scannedAt,
};
