// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'commerce_vendor_brief.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommerceVendorBrief _$CommerceVendorBriefFromJson(Map<String, dynamic> json) =>
    $checkedCreate('CommerceVendorBrief', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['id', 'name', 'description', 'kind', 'currency'],
      );
      final val = CommerceVendorBrief(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
        description: $checkedConvert('description', (v) => v as String?),
        kind: $checkedConvert(
          'kind',
          (v) => $enumDecode(
            _$CommerceVendorBriefKindEnumEnumMap,
            v,
            unknownValue: CommerceVendorBriefKindEnum.unknownDefaultOpenApi,
          ),
        ),
        currency: $checkedConvert('currency', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$CommerceVendorBriefToJson(
  CommerceVendorBrief instance,
) => <String, dynamic>{
  'id': instance.id,
  'name': instance.name,
  'description': instance.description,
  'kind': _$CommerceVendorBriefKindEnumEnumMap[instance.kind]!,
  'currency': instance.currency,
};

const _$CommerceVendorBriefKindEnumEnumMap = {
  CommerceVendorBriefKindEnum.school: 'school',
  CommerceVendorBriefKindEnum.independent: 'independent',
  CommerceVendorBriefKindEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
