// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'risk_assignment_result.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RiskAssignmentResult _$RiskAssignmentResultFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('RiskAssignmentResult', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['id', 'starts_on', 'ends_on', 'revoked', 'revision'],
  );
  final val = RiskAssignmentResult(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    startsOn: $checkedConvert('starts_on', (v) => v as String),
    endsOn: $checkedConvert('ends_on', (v) => v as String),
    revoked: $checkedConvert('revoked', (v) => v as bool),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
  );
  return val;
}, fieldKeyMap: const {'startsOn': 'starts_on', 'endsOn': 'ends_on'});

Map<String, dynamic> _$RiskAssignmentResultToJson(
  RiskAssignmentResult instance,
) => <String, dynamic>{
  'id': instance.id,
  'starts_on': instance.startsOn,
  'ends_on': instance.endsOn,
  'revoked': instance.revoked,
  'revision': instance.revision,
};
