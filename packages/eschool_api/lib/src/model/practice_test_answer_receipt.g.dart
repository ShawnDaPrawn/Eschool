// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_test_answer_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeTestAnswerReceipt _$PracticeTestAnswerReceiptFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'PracticeTestAnswerReceipt',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['id', 'saved', 'server_time', 'deadline_at'],
    );
    final val = PracticeTestAnswerReceipt(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      saved: $checkedConvert('saved', (v) => (v as num).toInt()),
      serverTime: $checkedConvert(
        'server_time',
        (v) => DateTime.parse(v as String),
      ),
      deadlineAt: $checkedConvert(
        'deadline_at',
        (v) => DateTime.parse(v as String),
      ),
    );
    return val;
  },
  fieldKeyMap: const {'serverTime': 'server_time', 'deadlineAt': 'deadline_at'},
);

Map<String, dynamic> _$PracticeTestAnswerReceiptToJson(
  PracticeTestAnswerReceipt instance,
) => <String, dynamic>{
  'id': instance.id,
  'saved': instance.saved,
  'server_time': instance.serverTime.toIso8601String(),
  'deadline_at': instance.deadlineAt.toIso8601String(),
};
