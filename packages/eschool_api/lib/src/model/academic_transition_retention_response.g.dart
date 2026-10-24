// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_transition_retention_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicTransitionRetentionResponse
_$AcademicTransitionRetentionResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('AcademicTransitionRetentionResponse', json, (
      $checkedConvert,
    ) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'code', 'data'],
      );
      final val = AcademicTransitionRetentionResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        code: $checkedConvert('code', (v) => (v as num).toInt()),
        data: $checkedConvert(
          'data',
          (v) =>
              AcademicTransitionRetention.fromJson(v as Map<String, dynamic>),
        ),
      );
      return val;
    });

Map<String, dynamic> _$AcademicTransitionRetentionResponseToJson(
  AcademicTransitionRetentionResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
