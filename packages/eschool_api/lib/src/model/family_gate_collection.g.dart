// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_gate_collection.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyGateCollection _$FamilyGateCollectionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'FamilyGateCollection',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'revision',
        'scan_id',
        'collector_confirmed',
      ],
    );
    final val = FamilyGateCollection(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      scanId: $checkedConvert('scan_id', (v) => v as String),
      collectorConfirmed: $checkedConvert(
        'collector_confirmed',
        (v) => v as bool,
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'scanId': 'scan_id',
    'collectorConfirmed': 'collector_confirmed',
  },
);

Map<String, dynamic> _$FamilyGateCollectionToJson(
  FamilyGateCollection instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'revision': instance.revision,
  'scan_id': instance.scanId,
  'collector_confirmed': instance.collectorConfirmed,
};
