// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'communication_audience_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommunicationAudienceReceipt _$CommunicationAudienceReceiptFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CommunicationAudienceReceipt', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'revision']);
  final val = CommunicationAudienceReceipt(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$CommunicationAudienceReceiptToJson(
  CommunicationAudienceReceipt instance,
) => <String, dynamic>{'id': instance.id, 'revision': instance.revision};
