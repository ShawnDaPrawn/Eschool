// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'upload_identified_incident_photo_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

UploadIdentifiedIncidentPhotoResponse
_$UploadIdentifiedIncidentPhotoResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('UploadIdentifiedIncidentPhotoResponse', json, (
      $checkedConvert,
    ) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'data', 'code'],
      );
      final val = UploadIdentifiedIncidentPhotoResponse(
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

Map<String, dynamic> _$UploadIdentifiedIncidentPhotoResponseToJson(
  UploadIdentifiedIncidentPhotoResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
