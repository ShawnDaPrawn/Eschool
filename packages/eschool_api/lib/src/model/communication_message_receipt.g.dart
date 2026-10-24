// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'communication_message_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommunicationMessageReceipt _$CommunicationMessageReceiptFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CommunicationMessageReceipt', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'revision', 'state']);
  final val = CommunicationMessageReceipt(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    state: $checkedConvert(
      'state',
      (v) => $enumDecode(
        _$CommunicationMessageReceiptStateEnumEnumMap,
        v,
        unknownValue:
            CommunicationMessageReceiptStateEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$CommunicationMessageReceiptToJson(
  CommunicationMessageReceipt instance,
) => <String, dynamic>{
  'id': instance.id,
  'revision': instance.revision,
  'state': _$CommunicationMessageReceiptStateEnumEnumMap[instance.state]!,
};

const _$CommunicationMessageReceiptStateEnumEnumMap = {
  CommunicationMessageReceiptStateEnum.draft: 'draft',
  CommunicationMessageReceiptStateEnum.pending: 'pending',
  CommunicationMessageReceiptStateEnum.approved: 'approved',
  CommunicationMessageReceiptStateEnum.dispatching: 'dispatching',
  CommunicationMessageReceiptStateEnum.sent: 'sent',
  CommunicationMessageReceiptStateEnum.rejected: 'rejected',
  CommunicationMessageReceiptStateEnum.blocked: 'blocked',
  CommunicationMessageReceiptStateEnum.withdrawn: 'withdrawn',
  CommunicationMessageReceiptStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
