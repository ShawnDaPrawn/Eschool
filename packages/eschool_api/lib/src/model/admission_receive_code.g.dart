// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'admission_receive_code.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AdmissionReceiveCode _$AdmissionReceiveCodeFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AdmissionReceiveCode',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school_id',
        'actor_user_id',
        'receive_code',
        'expires_at',
      ],
    );
    final val = AdmissionReceiveCode(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      actorUserId: $checkedConvert('actor_user_id', (v) => (v as num).toInt()),
      receiveCode: $checkedConvert('receive_code', (v) => v as String),
      expiresAt: $checkedConvert(
        'expires_at',
        (v) => DateTime.parse(v as String),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'actorUserId': 'actor_user_id',
    'receiveCode': 'receive_code',
    'expiresAt': 'expires_at',
  },
);

Map<String, dynamic> _$AdmissionReceiveCodeToJson(
  AdmissionReceiveCode instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'actor_user_id': instance.actorUserId,
  'receive_code': instance.receiveCode,
  'expires_at': instance.expiresAt.toIso8601String(),
};
