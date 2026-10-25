// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'transfer_write_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TransferWriteReceipt _$TransferWriteReceiptFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TransferWriteReceipt', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'revision', 'kind']);
  final val = TransferWriteReceipt(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    kind: $checkedConvert(
      'kind',
      (v) => $enumDecode(
        _$TransferWriteReceiptKindEnumEnumMap,
        v,
        unknownValue: TransferWriteReceiptKindEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$TransferWriteReceiptToJson(
  TransferWriteReceipt instance,
) => <String, dynamic>{
  'id': instance.id,
  'revision': instance.revision,
  'kind': _$TransferWriteReceiptKindEnumEnumMap[instance.kind]!,
};

const _$TransferWriteReceiptKindEnumEnumMap = {
  TransferWriteReceiptKindEnum.package: 'package',
  TransferWriteReceiptKindEnum.receipt: 'receipt',
  TransferWriteReceiptKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
