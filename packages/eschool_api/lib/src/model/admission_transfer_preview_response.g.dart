// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'admission_transfer_preview_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AdmissionTransferPreviewResponse _$AdmissionTransferPreviewResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AdmissionTransferPreviewResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = AdmissionTransferPreviewResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => AdmissionTransferPreview.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$AdmissionTransferPreviewResponseToJson(
  AdmissionTransferPreviewResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
