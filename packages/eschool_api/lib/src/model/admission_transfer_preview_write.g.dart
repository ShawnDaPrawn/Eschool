// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'admission_transfer_preview_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AdmissionTransferPreviewWrite _$AdmissionTransferPreviewWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AdmissionTransferPreviewWrite', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['receive_code']);
  final val = AdmissionTransferPreviewWrite(
    receiveCode: $checkedConvert('receive_code', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'receiveCode': 'receive_code'});

Map<String, dynamic> _$AdmissionTransferPreviewWriteToJson(
  AdmissionTransferPreviewWrite instance,
) => <String, dynamic>{'receive_code': instance.receiveCode};
