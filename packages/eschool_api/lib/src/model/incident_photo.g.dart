// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'incident_photo.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

IncidentPhoto _$IncidentPhotoFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'IncidentPhoto',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'mime',
            'bytes',
            'width',
            'height',
            'thumbnail_base64',
            'created_at',
            'report_revision',
          ],
        );
        final val = IncidentPhoto(
          id: $checkedConvert('id', (v) => v as String),
          mime: $checkedConvert(
            'mime',
            (v) => $enumDecode(
              _$IncidentPhotoMimeEnumEnumMap,
              v,
              unknownValue: IncidentPhotoMimeEnum.unknownDefaultOpenApi,
            ),
          ),
          bytes: $checkedConvert('bytes', (v) => (v as num).toInt()),
          width: $checkedConvert('width', (v) => (v as num).toInt()),
          height: $checkedConvert('height', (v) => (v as num).toInt()),
          thumbnailBase64: $checkedConvert(
            'thumbnail_base64',
            (v) => v as String,
          ),
          createdAt: $checkedConvert(
            'created_at',
            (v) => DateTime.parse(v as String),
          ),
          reportRevision: $checkedConvert(
            'report_revision',
            (v) => (v as num).toInt(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'thumbnailBase64': 'thumbnail_base64',
        'createdAt': 'created_at',
        'reportRevision': 'report_revision',
      },
    );

Map<String, dynamic> _$IncidentPhotoToJson(IncidentPhoto instance) =>
    <String, dynamic>{
      'id': instance.id,
      'mime': _$IncidentPhotoMimeEnumEnumMap[instance.mime]!,
      'bytes': instance.bytes,
      'width': instance.width,
      'height': instance.height,
      'thumbnail_base64': instance.thumbnailBase64,
      'created_at': instance.createdAt.toIso8601String(),
      'report_revision': instance.reportRevision,
    };

const _$IncidentPhotoMimeEnumEnumMap = {
  IncidentPhotoMimeEnum.imageSlashJpeg: 'image/jpeg',
  IncidentPhotoMimeEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
