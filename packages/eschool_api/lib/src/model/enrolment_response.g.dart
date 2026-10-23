// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'enrolment_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

EnrolmentResponse _$EnrolmentResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('EnrolmentResponse', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'data', 'code'],
      );
      final val = EnrolmentResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        data: $checkedConvert(
          'data',
          (v) => Enrolment.fromJson(v as Map<String, dynamic>),
        ),
        code: $checkedConvert('code', (v) => (v as num).toInt()),
      );
      return val;
    });

Map<String, dynamic> _$EnrolmentResponseToJson(EnrolmentResponse instance) =>
    <String, dynamic>{
      'error': instance.error,
      'message': instance.message,
      'data': instance.data.toJson(),
      'code': instance.code,
    };
