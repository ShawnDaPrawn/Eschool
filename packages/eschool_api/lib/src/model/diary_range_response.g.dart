// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'diary_range_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DiaryRangeResponse _$DiaryRangeResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('DiaryRangeResponse', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'data', 'code'],
      );
      final val = DiaryRangeResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        data: $checkedConvert(
          'data',
          (v) => DiaryRange.fromJson(v as Map<String, dynamic>),
        ),
        code: $checkedConvert('code', (v) => (v as num).toInt()),
      );
      return val;
    });

Map<String, dynamic> _$DiaryRangeResponseToJson(DiaryRangeResponse instance) =>
    <String, dynamic>{
      'error': instance.error,
      'message': instance.message,
      'data': instance.data.toJson(),
      'code': instance.code,
    };
