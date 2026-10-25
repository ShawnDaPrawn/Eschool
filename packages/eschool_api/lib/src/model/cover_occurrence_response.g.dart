// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'cover_occurrence_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CoverOccurrenceResponse _$CoverOccurrenceResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CoverOccurrenceResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = CoverOccurrenceResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => CoverOccurrence.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$CoverOccurrenceResponseToJson(
  CoverOccurrenceResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
