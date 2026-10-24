// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'communication_message_editor_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommunicationMessageEditorResponse _$CommunicationMessageEditorResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CommunicationMessageEditorResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = CommunicationMessageEditorResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => CommunicationMessageEditor.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$CommunicationMessageEditorResponseToJson(
  CommunicationMessageEditorResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
