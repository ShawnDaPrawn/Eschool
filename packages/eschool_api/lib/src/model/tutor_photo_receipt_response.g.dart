// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'tutor_photo_receipt_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TutorPhotoReceiptResponse _$TutorPhotoReceiptResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TutorPhotoReceiptResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = TutorPhotoReceiptResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => TutorPhotoReceipt.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$TutorPhotoReceiptResponseToJson(
  TutorPhotoReceiptResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
