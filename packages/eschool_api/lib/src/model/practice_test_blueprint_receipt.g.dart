// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_test_blueprint_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeTestBlueprintReceipt _$PracticeTestBlueprintReceiptFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('PracticeTestBlueprintReceipt', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'revision', 'active']);
  final val = PracticeTestBlueprintReceipt(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    active: $checkedConvert('active', (v) => v as bool),
  );
  return val;
});

Map<String, dynamic> _$PracticeTestBlueprintReceiptToJson(
  PracticeTestBlueprintReceipt instance,
) => <String, dynamic>{
  'id': instance.id,
  'revision': instance.revision,
  'active': instance.active,
};
