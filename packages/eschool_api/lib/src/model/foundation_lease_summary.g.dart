// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'foundation_lease_summary.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FoundationLeaseSummary _$FoundationLeaseSummaryFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'FoundationLeaseSummary',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'device_id',
        'teaching_group_id',
        'expires_at',
        'active',
      ],
    );
    final val = FoundationLeaseSummary(
      id: $checkedConvert('id', (v) => v as String),
      deviceId: $checkedConvert('device_id', (v) => v as String),
      teachingGroupId: $checkedConvert(
        'teaching_group_id',
        (v) => (v as num).toInt(),
      ),
      expiresAt: $checkedConvert(
        'expires_at',
        (v) => DateTime.parse(v as String),
      ),
      active: $checkedConvert('active', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'deviceId': 'device_id',
    'teachingGroupId': 'teaching_group_id',
    'expiresAt': 'expires_at',
  },
);

Map<String, dynamic> _$FoundationLeaseSummaryToJson(
  FoundationLeaseSummary instance,
) => <String, dynamic>{
  'id': instance.id,
  'device_id': instance.deviceId,
  'teaching_group_id': instance.teachingGroupId,
  'expires_at': instance.expiresAt.toIso8601String(),
  'active': instance.active,
};
