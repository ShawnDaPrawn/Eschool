// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_transition_grade_policy_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicTransitionGradePolicyResponse
_$AcademicTransitionGradePolicyResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('AcademicTransitionGradePolicyResponse', json, (
      $checkedConvert,
    ) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'code', 'data'],
      );
      final val = AcademicTransitionGradePolicyResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        code: $checkedConvert('code', (v) => (v as num).toInt()),
        data: $checkedConvert(
          'data',
          (v) =>
              AcademicTransitionGradePolicy.fromJson(v as Map<String, dynamic>),
        ),
      );
      return val;
    });

Map<String, dynamic> _$AcademicTransitionGradePolicyResponseToJson(
  AcademicTransitionGradePolicyResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
