// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'draft_bursary_discount_request.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DraftBursaryDiscountRequest _$DraftBursaryDiscountRequestFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('DraftBursaryDiscountRequest', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['operation_id']);
  final val = DraftBursaryDiscountRequest(
    operationId: $checkedConvert('operation_id', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$DraftBursaryDiscountRequestToJson(
  DraftBursaryDiscountRequest instance,
) => <String, dynamic>{'operation_id': instance.operationId};
