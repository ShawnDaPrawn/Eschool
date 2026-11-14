// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learner_academic_competition_list_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearnerAcademicCompetitionListResponse
_$LearnerAcademicCompetitionListResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('LearnerAcademicCompetitionListResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = LearnerAcademicCompetitionListResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => LearnerAcademicCompetitionList.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$LearnerAcademicCompetitionListResponseToJson(
  LearnerAcademicCompetitionListResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
