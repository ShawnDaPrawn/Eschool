// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_register_sync_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicRegisterSyncWrite _$AcademicRegisterSyncWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicRegisterSyncWrite',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'base_revision',
        'roster_hash',
        'rows',
      ],
    );
    final val = AcademicRegisterSyncWrite(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      baseRevision: $checkedConvert('base_revision', (v) => (v as num).toInt()),
      rosterHash: $checkedConvert('roster_hash', (v) => v as String),
      reason: $checkedConvert('reason', (v) => v as String?),
      rows: $checkedConvert(
        'rows',
        (v) => (v as List<dynamic>)
            .map(
              (e) =>
                  AcademicRegisterRowWrite.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'baseRevision': 'base_revision',
    'rosterHash': 'roster_hash',
  },
);

Map<String, dynamic> _$AcademicRegisterSyncWriteToJson(
  AcademicRegisterSyncWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'base_revision': instance.baseRevision,
  'roster_hash': instance.rosterHash,
  'reason': ?instance.reason,
  'rows': instance.rows.map((e) => e.toJson()).toList(),
};
