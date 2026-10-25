// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wellbeing_case_status_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WellbeingCaseStatusInput _$WellbeingCaseStatusInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('WellbeingCaseStatusInput', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['operation_id', 'revision', 'status', 'reason'],
  );
  final val = WellbeingCaseStatusInput(
    operationId: $checkedConvert('operation_id', (v) => v as String),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    status: $checkedConvert(
      'status',
      (v) => $enumDecode(
        _$WellbeingCaseStatusInputStatusEnumEnumMap,
        v,
        unknownValue: WellbeingCaseStatusInputStatusEnum.unknownDefaultOpenApi,
      ),
    ),
    reason: $checkedConvert('reason', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$WellbeingCaseStatusInputToJson(
  WellbeingCaseStatusInput instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'revision': instance.revision,
  'status': _$WellbeingCaseStatusInputStatusEnumEnumMap[instance.status]!,
  'reason': instance.reason,
};

const _$WellbeingCaseStatusInputStatusEnumEnumMap = {
  WellbeingCaseStatusInputStatusEnum.open: 'open',
  WellbeingCaseStatusInputStatusEnum.closed: 'closed',
  WellbeingCaseStatusInputStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
