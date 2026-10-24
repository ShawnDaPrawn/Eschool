// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_term_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolTermResponse _$SchoolTermResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('SchoolTermResponse', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'code', 'data'],
      );
      final val = SchoolTermResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        code: $checkedConvert('code', (v) => (v as num).toInt()),
        data: $checkedConvert(
          'data',
          (v) => SchoolTerm.fromJson(v as Map<String, dynamic>),
        ),
      );
      return val;
    });

Map<String, dynamic> _$SchoolTermResponseToJson(SchoolTermResponse instance) =>
    <String, dynamic>{
      'error': instance.error,
      'message': instance.message,
      'code': instance.code,
      'data': instance.data.toJson(),
    };
