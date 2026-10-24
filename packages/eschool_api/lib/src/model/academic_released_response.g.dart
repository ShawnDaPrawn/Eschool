// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_released_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReleasedResponse _$AcademicReleasedResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicReleasedResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = AcademicReleasedResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => AcademicReleasedData.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicReleasedResponseToJson(
  AcademicReleasedResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
