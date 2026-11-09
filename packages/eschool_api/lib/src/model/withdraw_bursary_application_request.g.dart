// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'withdraw_bursary_application_request.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WithdrawBursaryApplicationRequest _$WithdrawBursaryApplicationRequestFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('WithdrawBursaryApplicationRequest', json, (
  $checkedConvert,
) {
  $checkKeys(
    json,
    requiredKeys: const ['operation_id', 'revision', 'confirmed'],
  );
  final val = WithdrawBursaryApplicationRequest(
    operationId: $checkedConvert('operation_id', (v) => v as String),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    confirmed: $checkedConvert(
      'confirmed',
      (v) => WithdrawBursaryApplicationRequestConfirmedEnum.fromJson(v),
    ),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$WithdrawBursaryApplicationRequestToJson(
  WithdrawBursaryApplicationRequest instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'revision': instance.revision,
  'confirmed': ?WithdrawBursaryApplicationRequestConfirmedEnum.toJsonValue(
    instance.confirmed,
  ),
};
