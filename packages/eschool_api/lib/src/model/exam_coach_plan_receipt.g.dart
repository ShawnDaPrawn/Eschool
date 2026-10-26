// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_plan_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachPlanReceipt _$ExamCoachPlanReceiptFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ExamCoachPlanReceipt', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'kind', 'state']);
  final val = ExamCoachPlanReceipt(
    id: $checkedConvert('id', (v) => v as String),
    kind: $checkedConvert(
      'kind',
      (v) => $enumDecode(
        _$ExamCoachPlanReceiptKindEnumEnumMap,
        v,
        unknownValue: ExamCoachPlanReceiptKindEnum.unknownDefaultOpenApi,
      ),
    ),
    state: $checkedConvert(
      'state',
      (v) => $enumDecode(
        _$ExamCoachPlanReceiptStateEnumEnumMap,
        v,
        unknownValue: ExamCoachPlanReceiptStateEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$ExamCoachPlanReceiptToJson(
  ExamCoachPlanReceipt instance,
) => <String, dynamic>{
  'id': instance.id,
  'kind': _$ExamCoachPlanReceiptKindEnumEnumMap[instance.kind]!,
  'state': _$ExamCoachPlanReceiptStateEnumEnumMap[instance.state]!,
};

const _$ExamCoachPlanReceiptKindEnumEnumMap = {
  ExamCoachPlanReceiptKindEnum.studyPlan: 'study_plan',
  ExamCoachPlanReceiptKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$ExamCoachPlanReceiptStateEnumEnumMap = {
  ExamCoachPlanReceiptStateEnum.created: 'created',
  ExamCoachPlanReceiptStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
