// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'incident_photo_read_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

IncidentPhotoReadInput _$IncidentPhotoReadInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('IncidentPhotoReadInput', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['secret', 'photo_id']);
  final val = IncidentPhotoReadInput(
    secret: $checkedConvert('secret', (v) => v as String),
    photoId: $checkedConvert('photo_id', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'photoId': 'photo_id'});

Map<String, dynamic> _$IncidentPhotoReadInputToJson(
  IncidentPhotoReadInput instance,
) => <String, dynamic>{'secret': instance.secret, 'photo_id': instance.photoId};
