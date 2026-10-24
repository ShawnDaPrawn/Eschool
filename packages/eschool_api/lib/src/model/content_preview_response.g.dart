// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'content_preview_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ContentPreviewResponse _$ContentPreviewResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ContentPreviewResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = ContentPreviewResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => ContentPreview.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$ContentPreviewResponseToJson(
  ContentPreviewResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
