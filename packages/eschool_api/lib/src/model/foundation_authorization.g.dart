// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'foundation_authorization.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FoundationAuthorization _$FoundationAuthorizationFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'FoundationAuthorization',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'device_id',
        'teaching_group_id',
        'classroom_authorization',
        'expires_at',
      ],
    );
    final val = FoundationAuthorization(
      id: $checkedConvert('id', (v) => v as String),
      deviceId: $checkedConvert('device_id', (v) => v as String),
      teachingGroupId: $checkedConvert(
        'teaching_group_id',
        (v) => (v as num).toInt(),
      ),
      classroomAuthorization: $checkedConvert(
        'classroom_authorization',
        (v) => v as String,
      ),
      expiresAt: $checkedConvert(
        'expires_at',
        (v) => DateTime.parse(v as String),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'deviceId': 'device_id',
    'teachingGroupId': 'teaching_group_id',
    'classroomAuthorization': 'classroom_authorization',
    'expiresAt': 'expires_at',
  },
);

Map<String, dynamic> _$FoundationAuthorizationToJson(
  FoundationAuthorization instance,
) => <String, dynamic>{
  'id': instance.id,
  'device_id': instance.deviceId,
  'teaching_group_id': instance.teachingGroupId,
  'classroom_authorization': instance.classroomAuthorization,
  'expires_at': instance.expiresAt.toIso8601String(),
};
