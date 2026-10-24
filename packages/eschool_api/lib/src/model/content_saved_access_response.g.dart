// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'content_saved_access_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ContentSavedAccessResponse _$ContentSavedAccessResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ContentSavedAccessResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = ContentSavedAccessResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => ContentSavedAccess.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$ContentSavedAccessResponseToJson(
  ContentSavedAccessResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
