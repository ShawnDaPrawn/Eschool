// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'content_version_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ContentVersionResponse _$ContentVersionResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ContentVersionResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = ContentVersionResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => ContentVersion.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$ContentVersionResponseToJson(
  ContentVersionResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
