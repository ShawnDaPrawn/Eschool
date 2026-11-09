// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'quiet_hours_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

QuietHoursResponse _$QuietHoursResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('QuietHoursResponse', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'code', 'data'],
      );
      final val = QuietHoursResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        code: $checkedConvert('code', (v) => (v as num).toInt()),
        data: $checkedConvert(
          'data',
          (v) => QuietHoursResponseData.fromJson(v as Map<String, dynamic>),
        ),
      );
      return val;
    });

Map<String, dynamic> _$QuietHoursResponseToJson(QuietHoursResponse instance) =>
    <String, dynamic>{
      'error': instance.error,
      'message': instance.message,
      'code': instance.code,
      'data': instance.data.toJson(),
    };
