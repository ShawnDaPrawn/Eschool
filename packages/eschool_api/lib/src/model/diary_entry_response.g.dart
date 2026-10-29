// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'diary_entry_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DiaryEntryResponse _$DiaryEntryResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('DiaryEntryResponse', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'data', 'code'],
      );
      final val = DiaryEntryResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        data: $checkedConvert(
          'data',
          (v) => DiaryEntry.fromJson(v as Map<String, dynamic>),
        ),
        code: $checkedConvert('code', (v) => (v as num).toInt()),
      );
      return val;
    });

Map<String, dynamic> _$DiaryEntryResponseToJson(DiaryEntryResponse instance) =>
    <String, dynamic>{
      'error': instance.error,
      'message': instance.message,
      'data': instance.data.toJson(),
      'code': instance.code,
    };
