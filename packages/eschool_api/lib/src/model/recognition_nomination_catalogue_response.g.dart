// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'recognition_nomination_catalogue_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RecognitionNominationCatalogueResponse
_$RecognitionNominationCatalogueResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('RecognitionNominationCatalogueResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = RecognitionNominationCatalogueResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => RecognitionNominationCatalogue.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$RecognitionNominationCatalogueResponseToJson(
  RecognitionNominationCatalogueResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
