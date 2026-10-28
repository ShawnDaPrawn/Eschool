// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'admission_counter_resolution.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AdmissionCounterResolution _$AdmissionCounterResolutionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AdmissionCounterResolution',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school_id',
        'actor_user_id',
        'vendor_id',
        'operation_id',
        'status',
        'receipt',
      ],
    );
    final val = AdmissionCounterResolution(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      actorUserId: $checkedConvert('actor_user_id', (v) => (v as num).toInt()),
      vendorId: $checkedConvert('vendor_id', (v) => (v as num).toInt()),
      operationId: $checkedConvert('operation_id', (v) => v as String),
      status: $checkedConvert(
        'status',
        (v) => $enumDecode(
          _$AdmissionCounterResolutionStatusEnumEnumMap,
          v,
          unknownValue:
              AdmissionCounterResolutionStatusEnum.unknownDefaultOpenApi,
        ),
      ),
      receipt: $checkedConvert(
        'receipt',
        (v) => v == null
            ? null
            : AdmissionCounterTicket.fromJson(v as Map<String, dynamic>),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'actorUserId': 'actor_user_id',
    'vendorId': 'vendor_id',
    'operationId': 'operation_id',
  },
);

Map<String, dynamic> _$AdmissionCounterResolutionToJson(
  AdmissionCounterResolution instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'actor_user_id': instance.actorUserId,
  'vendor_id': instance.vendorId,
  'operation_id': instance.operationId,
  'status': _$AdmissionCounterResolutionStatusEnumEnumMap[instance.status]!,
  'receipt': instance.receipt?.toJson(),
};

const _$AdmissionCounterResolutionStatusEnumEnumMap = {
  AdmissionCounterResolutionStatusEnum.committed: 'committed',
  AdmissionCounterResolutionStatusEnum.abandoned: 'abandoned',
  AdmissionCounterResolutionStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
