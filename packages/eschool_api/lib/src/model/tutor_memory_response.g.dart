// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'tutor_memory_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TutorMemoryResponse _$TutorMemoryResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('TutorMemoryResponse', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'data', 'code'],
      );
      final val = TutorMemoryResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        data: $checkedConvert(
          'data',
          (v) => TutorMemory.fromJson(v as Map<String, dynamic>),
        ),
        code: $checkedConvert('code', (v) => (v as num).toInt()),
      );
      return val;
    });

Map<String, dynamic> _$TutorMemoryResponseToJson(
  TutorMemoryResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
