// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_detention_enrollment_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineDetentionEnrollmentResponse
_$DisciplineDetentionEnrollmentResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('DisciplineDetentionEnrollmentResponse', json, (
      $checkedConvert,
    ) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'code', 'data'],
      );
      final val = DisciplineDetentionEnrollmentResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        code: $checkedConvert('code', (v) => (v as num).toInt()),
        data: $checkedConvert(
          'data',
          (v) =>
              DisciplineDetentionEnrollment.fromJson(v as Map<String, dynamic>),
        ),
      );
      return val;
    });

Map<String, dynamic> _$DisciplineDetentionEnrollmentResponseToJson(
  DisciplineDetentionEnrollmentResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
