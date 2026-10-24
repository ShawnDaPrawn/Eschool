// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'content_library_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ContentLibraryResponse _$ContentLibraryResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ContentLibraryResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = ContentLibraryResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => ContentLibrary.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$ContentLibraryResponseToJson(
  ContentLibraryResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
