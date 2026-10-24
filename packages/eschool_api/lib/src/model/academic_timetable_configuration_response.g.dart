// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_timetable_configuration_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicTimetableConfigurationResponse
_$AcademicTimetableConfigurationResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('AcademicTimetableConfigurationResponse', json, (
      $checkedConvert,
    ) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'code', 'data'],
      );
      final val = AcademicTimetableConfigurationResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        code: $checkedConvert('code', (v) => (v as num).toInt()),
        data: $checkedConvert(
          'data',
          (v) => AcademicTimetableConfigurationData.fromJson(
            v as Map<String, dynamic>,
          ),
        ),
      );
      return val;
    });

Map<String, dynamic> _$AcademicTimetableConfigurationResponseToJson(
  AcademicTimetableConfigurationResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
