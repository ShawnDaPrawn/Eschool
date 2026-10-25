// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wellbeing_case_open_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WellbeingCaseOpenReceipt _$WellbeingCaseOpenReceiptFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('WellbeingCaseOpenReceipt', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['id', 'learner_id', 'status', 'opened_at', 'revision'],
  );
  final val = WellbeingCaseOpenReceipt(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
    status: $checkedConvert(
      'status',
      (v) => $enumDecode(
        _$WellbeingCaseOpenReceiptStatusEnumEnumMap,
        v,
        unknownValue: WellbeingCaseOpenReceiptStatusEnum.unknownDefaultOpenApi,
      ),
    ),
    openedAt: $checkedConvert('opened_at', (v) => DateTime.parse(v as String)),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
  );
  return val;
}, fieldKeyMap: const {'learnerId': 'learner_id', 'openedAt': 'opened_at'});

Map<String, dynamic> _$WellbeingCaseOpenReceiptToJson(
  WellbeingCaseOpenReceipt instance,
) => <String, dynamic>{
  'id': instance.id,
  'learner_id': instance.learnerId,
  'status': _$WellbeingCaseOpenReceiptStatusEnumEnumMap[instance.status]!,
  'opened_at': instance.openedAt.toIso8601String(),
  'revision': instance.revision,
};

const _$WellbeingCaseOpenReceiptStatusEnumEnumMap = {
  WellbeingCaseOpenReceiptStatusEnum.open: 'open',
  WellbeingCaseOpenReceiptStatusEnum.closed: 'closed',
  WellbeingCaseOpenReceiptStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
