// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_release_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReleaseResponse _$AcademicReleaseResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicReleaseResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = AcademicReleaseResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => AcademicReleaseData.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicReleaseResponseToJson(
  AcademicReleaseResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
