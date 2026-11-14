// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'shared_library_item_summary_licence.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SharedLibraryItemSummaryLicence _$SharedLibraryItemSummaryLicenceFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SharedLibraryItemSummaryLicence', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['code', 'url']);
  final val = SharedLibraryItemSummaryLicence(
    code: $checkedConvert('code', (v) => v as String),
    url: $checkedConvert('url', (v) => v as String?),
  );
  return val;
});

Map<String, dynamic> _$SharedLibraryItemSummaryLicenceToJson(
  SharedLibraryItemSummaryLicence instance,
) => <String, dynamic>{'code': instance.code, 'url': instance.url};
