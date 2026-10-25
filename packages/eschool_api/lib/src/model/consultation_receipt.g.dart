// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'consultation_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ConsultationReceipt _$ConsultationReceiptFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ConsultationReceipt', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['id', 'revision', 'status']);
      final val = ConsultationReceipt(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        revision: $checkedConvert('revision', (v) => (v as num).toInt()),
        status: $checkedConvert(
          'status',
          (v) => $enumDecode(
            _$ConsultationReceiptStatusEnumEnumMap,
            v,
            unknownValue: ConsultationReceiptStatusEnum.unknownDefaultOpenApi,
          ),
        ),
      );
      return val;
    });

Map<String, dynamic> _$ConsultationReceiptToJson(
  ConsultationReceipt instance,
) => <String, dynamic>{
  'id': instance.id,
  'revision': instance.revision,
  'status': _$ConsultationReceiptStatusEnumEnumMap[instance.status]!,
};

const _$ConsultationReceiptStatusEnumEnumMap = {
  ConsultationReceiptStatusEnum.draft: 'draft',
  ConsultationReceiptStatusEnum.scheduled: 'scheduled',
  ConsultationReceiptStatusEnum.open: 'open',
  ConsultationReceiptStatusEnum.closed: 'closed',
  ConsultationReceiptStatusEnum.cancelled: 'cancelled',
  ConsultationReceiptStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
