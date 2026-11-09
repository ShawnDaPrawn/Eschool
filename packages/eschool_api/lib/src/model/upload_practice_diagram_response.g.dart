// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'upload_practice_diagram_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

UploadPracticeDiagramResponse _$UploadPracticeDiagramResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('UploadPracticeDiagramResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = UploadPracticeDiagramResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => PracticeDiagram.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$UploadPracticeDiagramResponseToJson(
  UploadPracticeDiagramResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
