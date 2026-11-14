// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_competition_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicCompetitionResponse _$AcademicCompetitionResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicCompetitionResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = AcademicCompetitionResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => AcademicCompetition.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicCompetitionResponseToJson(
  AcademicCompetitionResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
