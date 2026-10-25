// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wellbeing_case_status_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WellbeingCaseStatusReceipt _$WellbeingCaseStatusReceiptFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('WellbeingCaseStatusReceipt', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['id', 'learner_id', 'status', 'revision'],
  );
  final val = WellbeingCaseStatusReceipt(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
    status: $checkedConvert(
      'status',
      (v) => $enumDecode(
        _$WellbeingCaseStatusReceiptStatusEnumEnumMap,
        v,
        unknownValue:
            WellbeingCaseStatusReceiptStatusEnum.unknownDefaultOpenApi,
      ),
    ),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
  );
  return val;
}, fieldKeyMap: const {'learnerId': 'learner_id'});

Map<String, dynamic> _$WellbeingCaseStatusReceiptToJson(
  WellbeingCaseStatusReceipt instance,
) => <String, dynamic>{
  'id': instance.id,
  'learner_id': instance.learnerId,
  'status': _$WellbeingCaseStatusReceiptStatusEnumEnumMap[instance.status]!,
  'revision': instance.revision,
};

const _$WellbeingCaseStatusReceiptStatusEnumEnumMap = {
  WellbeingCaseStatusReceiptStatusEnum.open: 'open',
  WellbeingCaseStatusReceiptStatusEnum.closed: 'closed',
  WellbeingCaseStatusReceiptStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
