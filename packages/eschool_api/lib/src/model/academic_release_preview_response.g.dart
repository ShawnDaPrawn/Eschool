// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_release_preview_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReleasePreviewResponse _$AcademicReleasePreviewResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicReleasePreviewResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = AcademicReleasePreviewResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => AcademicReleasePreviewData.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicReleasePreviewResponseToJson(
  AcademicReleasePreviewResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
