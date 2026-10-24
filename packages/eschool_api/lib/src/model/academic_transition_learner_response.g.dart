// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_transition_learner_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicTransitionLearnerResponse _$AcademicTransitionLearnerResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicTransitionLearnerResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = AcademicTransitionLearnerResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => AcademicTransitionLearner.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicTransitionLearnerResponseToJson(
  AcademicTransitionLearnerResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
