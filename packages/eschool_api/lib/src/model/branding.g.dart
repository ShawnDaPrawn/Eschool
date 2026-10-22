// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'branding.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

Branding _$BrandingFromJson(Map<String, dynamic> json) => $checkedCreate(
  'Branding',
  json,
  ($checkedConvert) {
    $checkKeys(json, requiredKeys: const ['theme_color', 'secondary_color']);
    final val = Branding(
      themeColor: $checkedConvert('theme_color', (v) => v as String?),
      secondaryColor: $checkedConvert('secondary_color', (v) => v as String?),
    );
    return val;
  },
  fieldKeyMap: const {
    'themeColor': 'theme_color',
    'secondaryColor': 'secondary_color',
  },
);

Map<String, dynamic> _$BrandingToJson(Branding instance) => <String, dynamic>{
  'theme_color': instance.themeColor,
  'secondary_color': instance.secondaryColor,
};
