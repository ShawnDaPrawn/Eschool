// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'teaching_photo_revocation.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TeachingPhotoRevocation _$TeachingPhotoRevocationFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'TeachingPhotoRevocation',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school_id',
        'reader_id',
        'id',
        'revoked',
        'write_operation_id',
      ],
    );
    final val = TeachingPhotoRevocation(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
      id: $checkedConvert('id', (v) => v as String),
      revoked: $checkedConvert('revoked', (v) => v as bool),
      writeOperationId: $checkedConvert(
        'write_operation_id',
        (v) => v as String,
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'readerId': 'reader_id',
    'writeOperationId': 'write_operation_id',
  },
);

Map<String, dynamic> _$TeachingPhotoRevocationToJson(
  TeachingPhotoRevocation instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'reader_id': instance.readerId,
  'id': instance.id,
  'revoked': instance.revoked,
  'write_operation_id': instance.writeOperationId,
};
