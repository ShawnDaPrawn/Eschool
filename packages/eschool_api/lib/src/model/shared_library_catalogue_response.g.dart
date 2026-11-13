// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'shared_library_catalogue_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SharedLibraryCatalogueResponse _$SharedLibraryCatalogueResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SharedLibraryCatalogueResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = SharedLibraryCatalogueResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => SharedLibraryCatalogue.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$SharedLibraryCatalogueResponseToJson(
  SharedLibraryCatalogueResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
