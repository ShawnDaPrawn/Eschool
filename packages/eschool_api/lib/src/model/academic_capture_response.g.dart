// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_capture_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicCaptureResponse _$AcademicCaptureResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicCaptureResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = AcademicCaptureResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => AcademicCaptureData.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicCaptureResponseToJson(
  AcademicCaptureResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
