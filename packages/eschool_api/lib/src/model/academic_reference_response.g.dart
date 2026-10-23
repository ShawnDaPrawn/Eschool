// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_reference_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReferenceResponse _$AcademicReferenceResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicReferenceResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = AcademicReferenceResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => AcademicReference.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicReferenceResponseToJson(
  AcademicReferenceResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
