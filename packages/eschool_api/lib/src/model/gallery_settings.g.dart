// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'gallery_settings.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GallerySettings _$GallerySettingsFromJson(Map<String, dynamic> json) =>
    $checkedCreate('GallerySettings', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['guardian_required', 'revision']);
      final val = GallerySettings(
        guardianRequired: $checkedConvert(
          'guardian_required',
          (v) => v as bool,
        ),
        revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      );
      return val;
    }, fieldKeyMap: const {'guardianRequired': 'guardian_required'});

Map<String, dynamic> _$GallerySettingsToJson(GallerySettings instance) =>
    <String, dynamic>{
      'guardian_required': instance.guardianRequired,
      'revision': instance.revision,
    };
