// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'secure_offline_context.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SecureOfflineContext _$SecureOfflineContextFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'SecureOfflineContext',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school_id',
        'device_id',
        'snapshot_id',
        'issued_at',
        'expires_at',
      ],
    );
    final val = SecureOfflineContext(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      deviceId: $checkedConvert('device_id', (v) => v as String),
      snapshotId: $checkedConvert('snapshot_id', (v) => v as String),
      issuedAt: $checkedConvert('issued_at', (v) => (v as num).toInt()),
      expiresAt: $checkedConvert('expires_at', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'deviceId': 'device_id',
    'snapshotId': 'snapshot_id',
    'issuedAt': 'issued_at',
    'expiresAt': 'expires_at',
  },
);

Map<String, dynamic> _$SecureOfflineContextToJson(
  SecureOfflineContext instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'device_id': instance.deviceId,
  'snapshot_id': instance.snapshotId,
  'issued_at': instance.issuedAt,
  'expires_at': instance.expiresAt,
};
