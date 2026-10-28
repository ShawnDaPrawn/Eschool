// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'vendor_counter_resolution.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

VendorCounterResolution _$VendorCounterResolutionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'VendorCounterResolution',
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
        'order',
      ],
    );
    final val = VendorCounterResolution(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      actorUserId: $checkedConvert('actor_user_id', (v) => (v as num).toInt()),
      vendorId: $checkedConvert('vendor_id', (v) => (v as num).toInt()),
      operationId: $checkedConvert('operation_id', (v) => v as String),
      status: $checkedConvert(
        'status',
        (v) => $enumDecode(
          _$VendorCounterResolutionStatusEnumEnumMap,
          v,
          unknownValue: VendorCounterResolutionStatusEnum.unknownDefaultOpenApi,
        ),
      ),
      order: $checkedConvert(
        'order',
        (v) => v == null
            ? null
            : VendorCounterReceipt.fromJson(v as Map<String, dynamic>),
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

Map<String, dynamic> _$VendorCounterResolutionToJson(
  VendorCounterResolution instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'actor_user_id': instance.actorUserId,
  'vendor_id': instance.vendorId,
  'operation_id': instance.operationId,
  'status': _$VendorCounterResolutionStatusEnumEnumMap[instance.status]!,
  'order': instance.order?.toJson(),
};

const _$VendorCounterResolutionStatusEnumEnumMap = {
  VendorCounterResolutionStatusEnum.committed: 'committed',
  VendorCounterResolutionStatusEnum.abandoned: 'abandoned',
  VendorCounterResolutionStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
