// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'incident_reply_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

IncidentReplyInput _$IncidentReplyInputFromJson(Map<String, dynamic> json) =>
    $checkedCreate('IncidentReplyInput', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['secret', 'operation_id', 'body']);
      final val = IncidentReplyInput(
        secret: $checkedConvert('secret', (v) => v as String),
        operationId: $checkedConvert('operation_id', (v) => v as String),
        body: $checkedConvert('body', (v) => v as String),
      );
      return val;
    }, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$IncidentReplyInputToJson(IncidentReplyInput instance) =>
    <String, dynamic>{
      'secret': instance.secret,
      'operation_id': instance.operationId,
      'body': instance.body,
    };
