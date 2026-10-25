// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'tutor_photo_revocation.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TutorPhotoRevocation _$TutorPhotoRevocationFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TutorPhotoRevocation', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['school_id', 'reader_id', 'id', 'revoked'],
  );
  final val = TutorPhotoRevocation(
    schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
    readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
    id: $checkedConvert('id', (v) => v as String),
    revoked: $checkedConvert('revoked', (v) => v as bool),
  );
  return val;
}, fieldKeyMap: const {'schoolId': 'school_id', 'readerId': 'reader_id'});

Map<String, dynamic> _$TutorPhotoRevocationToJson(
  TutorPhotoRevocation instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'reader_id': instance.readerId,
  'id': instance.id,
  'revoked': instance.revoked,
};
