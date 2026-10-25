// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'tutor_photo_revocation_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TutorPhotoRevocationResponse _$TutorPhotoRevocationResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TutorPhotoRevocationResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = TutorPhotoRevocationResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => TutorPhotoRevocation.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$TutorPhotoRevocationResponseToJson(
  TutorPhotoRevocationResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
