// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_paper_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachPaperReceipt _$ExamCoachPaperReceiptFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ExamCoachPaperReceipt', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['id', 'version_id', 'revision', 'state'],
  );
  final val = ExamCoachPaperReceipt(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    versionId: $checkedConvert('version_id', (v) => v as String?),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    state: $checkedConvert(
      'state',
      (v) => $enumDecode(
        _$ExamCoachPaperReceiptStateEnumEnumMap,
        v,
        unknownValue: ExamCoachPaperReceiptStateEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
}, fieldKeyMap: const {'versionId': 'version_id'});

Map<String, dynamic> _$ExamCoachPaperReceiptToJson(
  ExamCoachPaperReceipt instance,
) => <String, dynamic>{
  'id': instance.id,
  'version_id': instance.versionId,
  'revision': instance.revision,
  'state': _$ExamCoachPaperReceiptStateEnumEnumMap[instance.state]!,
};

const _$ExamCoachPaperReceiptStateEnumEnumMap = {
  ExamCoachPaperReceiptStateEnum.draft: 'draft',
  ExamCoachPaperReceiptStateEnum.published: 'published',
  ExamCoachPaperReceiptStateEnum.withdrawn: 'withdrawn',
  ExamCoachPaperReceiptStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
