// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'support_write_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SupportWriteReceipt _$SupportWriteReceiptFromJson(Map<String, dynamic> json) =>
    $checkedCreate('SupportWriteReceipt', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['id', 'revision', 'kind']);
      final val = SupportWriteReceipt(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        revision: $checkedConvert('revision', (v) => (v as num).toInt()),
        kind: $checkedConvert(
          'kind',
          (v) => $enumDecode(
            _$SupportWriteReceiptKindEnumEnumMap,
            v,
            unknownValue: SupportWriteReceiptKindEnum.unknownDefaultOpenApi,
          ),
        ),
      );
      return val;
    });

Map<String, dynamic> _$SupportWriteReceiptToJson(
  SupportWriteReceipt instance,
) => <String, dynamic>{
  'id': instance.id,
  'revision': instance.revision,
  'kind': _$SupportWriteReceiptKindEnumEnumMap[instance.kind]!,
};

const _$SupportWriteReceiptKindEnumEnumMap = {
  SupportWriteReceiptKindEnum.assignment: 'assignment',
  SupportWriteReceiptKindEnum.plan: 'plan',
  SupportWriteReceiptKindEnum.review: 'review',
  SupportWriteReceiptKindEnum.disclosure: 'disclosure',
  SupportWriteReceiptKindEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
