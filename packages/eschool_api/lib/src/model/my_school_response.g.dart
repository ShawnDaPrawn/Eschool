// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'my_school_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

MySchoolResponse _$MySchoolResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('MySchoolResponse', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'data', 'code'],
      );
      final val = MySchoolResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        data: $checkedConvert(
          'data',
          (v) => MySchoolInfo.fromJson(v as Map<String, dynamic>),
        ),
        code: $checkedConvert('code', (v) => (v as num).toInt()),
      );
      return val;
    });

Map<String, dynamic> _$MySchoolResponseToJson(MySchoolResponse instance) =>
    <String, dynamic>{
      'error': instance.error,
      'message': instance.message,
      'data': instance.data.toJson(),
      'code': instance.code,
    };
