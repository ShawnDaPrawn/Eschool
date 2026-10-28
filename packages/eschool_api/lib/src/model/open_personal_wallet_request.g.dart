// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'open_personal_wallet_request.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

OpenPersonalWalletRequest _$OpenPersonalWalletRequestFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'OpenPersonalWalletRequest',
  json,
  ($checkedConvert) {
    $checkKeys(json, requiredKeys: const ['operation_id', 'owner_user_id']);
    final val = OpenPersonalWalletRequest(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      ownerUserId: $checkedConvert('owner_user_id', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'ownerUserId': 'owner_user_id',
  },
);

Map<String, dynamic> _$OpenPersonalWalletRequestToJson(
  OpenPersonalWalletRequest instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'owner_user_id': instance.ownerUserId,
};
