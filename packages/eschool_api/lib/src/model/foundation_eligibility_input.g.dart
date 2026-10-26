// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'foundation_eligibility_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FoundationEligibilityInput _$FoundationEligibilityInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('FoundationEligibilityInput', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['operation_id', 'revision', 'eligible', 'reason'],
  );
  final val = FoundationEligibilityInput(
    operationId: $checkedConvert('operation_id', (v) => v as String),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    eligible: $checkedConvert('eligible', (v) => v as bool),
    reason: $checkedConvert('reason', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$FoundationEligibilityInputToJson(
  FoundationEligibilityInput instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'revision': instance.revision,
  'eligible': instance.eligible,
  'reason': instance.reason,
};
