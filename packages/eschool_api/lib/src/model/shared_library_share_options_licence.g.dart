// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'shared_library_share_options_licence.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SharedLibraryShareOptionsLicence _$SharedLibraryShareOptionsLicenceFromJson(
  Map<String, dynamic> json,
) =>
    $checkedCreate('SharedLibraryShareOptionsLicence', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['code', 'name', 'url']);
      final val = SharedLibraryShareOptionsLicence(
        code: $checkedConvert('code', (v) => v as String),
        name: $checkedConvert('name', (v) => v as String),
        url: $checkedConvert('url', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$SharedLibraryShareOptionsLicenceToJson(
  SharedLibraryShareOptionsLicence instance,
) => <String, dynamic>{
  'code': instance.code,
  'name': instance.name,
  'url': instance.url,
};
