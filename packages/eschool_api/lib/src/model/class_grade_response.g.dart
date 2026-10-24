// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'class_grade_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ClassGradeResponse _$ClassGradeResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ClassGradeResponse', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'code', 'data'],
      );
      final val = ClassGradeResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        code: $checkedConvert('code', (v) => (v as num).toInt()),
        data: $checkedConvert(
          'data',
          (v) => ClassGradeMapping.fromJson(v as Map<String, dynamic>),
        ),
      );
      return val;
    });

Map<String, dynamic> _$ClassGradeResponseToJson(ClassGradeResponse instance) =>
    <String, dynamic>{
      'error': instance.error,
      'message': instance.message,
      'code': instance.code,
      'data': instance.data.toJson(),
    };
