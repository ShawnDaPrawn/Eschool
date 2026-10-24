// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_subject_standard_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolSubjectStandardResponse _$SchoolSubjectStandardResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SchoolSubjectStandardResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = SchoolSubjectStandardResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => SchoolSubjectStandard.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$SchoolSubjectStandardResponseToJson(
  SchoolSubjectStandardResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
