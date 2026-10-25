// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wellbeing_case_open_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WellbeingCaseOpenInput _$WellbeingCaseOpenInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WellbeingCaseOpenInput',
  json,
  ($checkedConvert) {
    $checkKeys(json, requiredKeys: const ['operation_id', 'learner_id']);
    final val = WellbeingCaseOpenInput(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {'operationId': 'operation_id', 'learnerId': 'learner_id'},
);

Map<String, dynamic> _$WellbeingCaseOpenInputToJson(
  WellbeingCaseOpenInput instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'learner_id': instance.learnerId,
};
