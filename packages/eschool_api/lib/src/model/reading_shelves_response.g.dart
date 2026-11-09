// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reading_shelves_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReadingShelvesResponse _$ReadingShelvesResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ReadingShelvesResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ReadingShelvesResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => ReadingShelves.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$ReadingShelvesResponseToJson(
  ReadingShelvesResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
