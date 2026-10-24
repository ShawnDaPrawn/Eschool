// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_bells_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicBellsResponse _$AcademicBellsResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicBellsResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = AcademicBellsResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => AcademicBells.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicBellsResponseToJson(
  AcademicBellsResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
