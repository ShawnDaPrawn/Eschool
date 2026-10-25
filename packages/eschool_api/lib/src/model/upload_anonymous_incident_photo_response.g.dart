// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'upload_anonymous_incident_photo_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

UploadAnonymousIncidentPhotoResponse
_$UploadAnonymousIncidentPhotoResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('UploadAnonymousIncidentPhotoResponse', json, (
      $checkedConvert,
    ) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'data', 'code'],
      );
      final val = UploadAnonymousIncidentPhotoResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        data: $checkedConvert(
          'data',
          (v) => IncidentPhoto.fromJson(v as Map<String, dynamic>),
        ),
        code: $checkedConvert('code', (v) => (v as num).toInt()),
      );
      return val;
    });

Map<String, dynamic> _$UploadAnonymousIncidentPhotoResponseToJson(
  UploadAnonymousIncidentPhotoResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
