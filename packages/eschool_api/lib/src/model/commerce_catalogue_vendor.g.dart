// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'commerce_catalogue_vendor.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommerceCatalogueVendor _$CommerceCatalogueVendorFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CommerceCatalogueVendor', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'name', 'description', 'kind']);
  final val = CommerceCatalogueVendor(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    name: $checkedConvert('name', (v) => v as String),
    description: $checkedConvert('description', (v) => v as String?),
    kind: $checkedConvert(
      'kind',
      (v) => $enumDecode(
        _$CommerceCatalogueVendorKindEnumEnumMap,
        v,
        unknownValue: CommerceCatalogueVendorKindEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$CommerceCatalogueVendorToJson(
  CommerceCatalogueVendor instance,
) => <String, dynamic>{
  'id': instance.id,
  'name': instance.name,
  'description': instance.description,
  'kind': _$CommerceCatalogueVendorKindEnumEnumMap[instance.kind]!,
};

const _$CommerceCatalogueVendorKindEnumEnumMap = {
  CommerceCatalogueVendorKindEnum.school: 'school',
  CommerceCatalogueVendorKindEnum.independent: 'independent',
  CommerceCatalogueVendorKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
