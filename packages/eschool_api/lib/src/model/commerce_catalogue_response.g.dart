// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'commerce_catalogue_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommerceCatalogueResponse _$CommerceCatalogueResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CommerceCatalogueResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = CommerceCatalogueResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => CommerceCatalogue.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$CommerceCatalogueResponseToJson(
  CommerceCatalogueResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
