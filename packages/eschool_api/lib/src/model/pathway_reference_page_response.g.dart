// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pathway_reference_page_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PathwayReferencePageResponse _$PathwayReferencePageResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('PathwayReferencePageResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = PathwayReferencePageResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => PathwayReferencePage.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$PathwayReferencePageResponseToJson(
  PathwayReferencePageResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
