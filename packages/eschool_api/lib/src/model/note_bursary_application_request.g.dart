// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'note_bursary_application_request.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

NoteBursaryApplicationRequest _$NoteBursaryApplicationRequestFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('NoteBursaryApplicationRequest', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['operation_id', 'reasons']);
  final val = NoteBursaryApplicationRequest(
    operationId: $checkedConvert('operation_id', (v) => v as String),
    reasons: $checkedConvert('reasons', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$NoteBursaryApplicationRequestToJson(
  NoteBursaryApplicationRequest instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'reasons': instance.reasons,
};
