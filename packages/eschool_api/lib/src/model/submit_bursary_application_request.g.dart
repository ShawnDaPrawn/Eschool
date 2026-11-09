// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'submit_bursary_application_request.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SubmitBursaryApplicationRequest _$SubmitBursaryApplicationRequestFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SubmitBursaryApplicationRequest', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['operation_id', 'revision', 'confirmed'],
  );
  final val = SubmitBursaryApplicationRequest(
    operationId: $checkedConvert('operation_id', (v) => v as String),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    confirmed: $checkedConvert(
      'confirmed',
      (v) => SubmitBursaryApplicationRequestConfirmedEnum.fromJson(v),
    ),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$SubmitBursaryApplicationRequestToJson(
  SubmitBursaryApplicationRequest instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'revision': instance.revision,
  'confirmed': ?SubmitBursaryApplicationRequestConfirmedEnum.toJsonValue(
    instance.confirmed,
  ),
};
