// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wellbeing_case_entry_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WellbeingCaseEntryReceipt _$WellbeingCaseEntryReceiptFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('WellbeingCaseEntryReceipt', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['case_revision', 'entry']);
  final val = WellbeingCaseEntryReceipt(
    caseRevision: $checkedConvert('case_revision', (v) => (v as num).toInt()),
    entry: $checkedConvert(
      'entry',
      (v) => WellbeingCaseEntryReceiptEntry.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
}, fieldKeyMap: const {'caseRevision': 'case_revision'});

Map<String, dynamic> _$WellbeingCaseEntryReceiptToJson(
  WellbeingCaseEntryReceipt instance,
) => <String, dynamic>{
  'case_revision': instance.caseRevision,
  'entry': instance.entry.toJson(),
};
