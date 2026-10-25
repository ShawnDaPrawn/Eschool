// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'incident_photo_constraints.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

IncidentPhotoConstraints _$IncidentPhotoConstraintsFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'IncidentPhotoConstraints',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['maximum_bytes', 'maximum_photos', 'allowed_mimes'],
    );
    final val = IncidentPhotoConstraints(
      maximumBytes: $checkedConvert('maximum_bytes', (v) => (v as num).toInt()),
      maximumPhotos: $checkedConvert(
        'maximum_photos',
        (v) => (v as num).toInt(),
      ),
      allowedMimes: $checkedConvert(
        'allowed_mimes',
        (v) => (v as List<dynamic>).map((e) => e as String).toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'maximumBytes': 'maximum_bytes',
    'maximumPhotos': 'maximum_photos',
    'allowedMimes': 'allowed_mimes',
  },
);

Map<String, dynamic> _$IncidentPhotoConstraintsToJson(
  IncidentPhotoConstraints instance,
) => <String, dynamic>{
  'maximum_bytes': instance.maximumBytes,
  'maximum_photos': instance.maximumPhotos,
  'allowed_mimes': instance.allowedMimes,
};
