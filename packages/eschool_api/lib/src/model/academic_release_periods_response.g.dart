// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_release_periods_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReleasePeriodsResponse _$AcademicReleasePeriodsResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicReleasePeriodsResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = AcademicReleasePeriodsResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => AcademicReleasePeriodsData.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicReleasePeriodsResponseToJson(
  AcademicReleasePeriodsResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
