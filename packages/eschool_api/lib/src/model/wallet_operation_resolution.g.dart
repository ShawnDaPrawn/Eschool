// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_operation_resolution.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletOperationResolution _$WalletOperationResolutionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WalletOperationResolution',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school_id',
        'actor_user_id',
        'operation_id',
        'status',
      ],
    );
    final val = WalletOperationResolution(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      actorUserId: $checkedConvert('actor_user_id', (v) => (v as num).toInt()),
      operationId: $checkedConvert('operation_id', (v) => v as String),
      status: $checkedConvert(
        'status',
        (v) => $enumDecode(
          _$WalletOperationResolutionStatusEnumEnumMap,
          v,
          unknownValue:
              WalletOperationResolutionStatusEnum.unknownDefaultOpenApi,
        ),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'actorUserId': 'actor_user_id',
    'operationId': 'operation_id',
  },
);

Map<String, dynamic> _$WalletOperationResolutionToJson(
  WalletOperationResolution instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'actor_user_id': instance.actorUserId,
  'operation_id': instance.operationId,
  'status': _$WalletOperationResolutionStatusEnumEnumMap[instance.status]!,
};

const _$WalletOperationResolutionStatusEnumEnumMap = {
  WalletOperationResolutionStatusEnum.committed: 'committed',
  WalletOperationResolutionStatusEnum.abandoned: 'abandoned',
  WalletOperationResolutionStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
