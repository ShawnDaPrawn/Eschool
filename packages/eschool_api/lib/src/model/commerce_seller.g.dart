// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'commerce_seller.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommerceSeller _$CommerceSellerFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'CommerceSeller',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'name',
            'legal_name',
            'legal_address',
            'kind',
            'vat_registered',
            'vat_number',
          ],
        );
        final val = CommerceSeller(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          name: $checkedConvert('name', (v) => v as String),
          legalName: $checkedConvert('legal_name', (v) => v as String),
          legalAddress: $checkedConvert('legal_address', (v) => v as String?),
          kind: $checkedConvert(
            'kind',
            (v) => $enumDecode(
              _$CommerceSellerKindEnumEnumMap,
              v,
              unknownValue: CommerceSellerKindEnum.unknownDefaultOpenApi,
            ),
          ),
          vatRegistered: $checkedConvert('vat_registered', (v) => v as bool),
          vatNumber: $checkedConvert('vat_number', (v) => v as String?),
        );
        return val;
      },
      fieldKeyMap: const {
        'legalName': 'legal_name',
        'legalAddress': 'legal_address',
        'vatRegistered': 'vat_registered',
        'vatNumber': 'vat_number',
      },
    );

Map<String, dynamic> _$CommerceSellerToJson(CommerceSeller instance) =>
    <String, dynamic>{
      'id': instance.id,
      'name': instance.name,
      'legal_name': instance.legalName,
      'legal_address': instance.legalAddress,
      'kind': _$CommerceSellerKindEnumEnumMap[instance.kind]!,
      'vat_registered': instance.vatRegistered,
      'vat_number': instance.vatNumber,
    };

const _$CommerceSellerKindEnumEnumMap = {
  CommerceSellerKindEnum.school: 'school',
  CommerceSellerKindEnum.independent: 'independent',
  CommerceSellerKindEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
