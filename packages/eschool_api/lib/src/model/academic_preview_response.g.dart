// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_preview_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicPreviewResponse _$AcademicPreviewResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicPreviewResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = AcademicPreviewResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => AcademicPreviewData.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicPreviewResponseToJson(
  AcademicPreviewResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
