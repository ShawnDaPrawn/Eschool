// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'secure_card_issue.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SecureCardIssue _$SecureCardIssueFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SecureCardIssue', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['operation_id', 'person_id', 'type']);
  final val = SecureCardIssue(
    operationId: $checkedConvert('operation_id', (v) => v as String),
    personId: $checkedConvert('person_id', (v) => (v as num).toInt()),
    type: $checkedConvert('type', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id', 'personId': 'person_id'});

Map<String, dynamic> _$SecureCardIssueToJson(SecureCardIssue instance) =>
    <String, dynamic>{
      'operation_id': instance.operationId,
      'person_id': instance.personId,
      'type': instance.type,
    };
