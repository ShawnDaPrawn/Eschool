// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'teaching_photo_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TeachingPhotoReceipt _$TeachingPhotoReceiptFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'TeachingPhotoReceipt',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school_id',
        'reader_id',
        'id',
        'sha256',
        'mime',
        'operation_id',
      ],
    );
    final val = TeachingPhotoReceipt(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
      id: $checkedConvert('id', (v) => v as String),
      sha256: $checkedConvert('sha256', (v) => v as String),
      mime: $checkedConvert(
        'mime',
        (v) => $enumDecode(
          _$TeachingPhotoReceiptMimeEnumEnumMap,
          v,
          unknownValue: TeachingPhotoReceiptMimeEnum.unknownDefaultOpenApi,
        ),
      ),
      operationId: $checkedConvert('operation_id', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'readerId': 'reader_id',
    'operationId': 'operation_id',
  },
);

Map<String, dynamic> _$TeachingPhotoReceiptToJson(
  TeachingPhotoReceipt instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'reader_id': instance.readerId,
  'id': instance.id,
  'sha256': instance.sha256,
  'mime': _$TeachingPhotoReceiptMimeEnumEnumMap[instance.mime]!,
  'operation_id': instance.operationId,
};

const _$TeachingPhotoReceiptMimeEnumEnumMap = {
  TeachingPhotoReceiptMimeEnum.imageSlashPng: 'image/png',
  TeachingPhotoReceiptMimeEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
