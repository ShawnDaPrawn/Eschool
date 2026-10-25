// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_record_metadata_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineRecordMetadataResponse _$DisciplineRecordMetadataResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('DisciplineRecordMetadataResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = DisciplineRecordMetadataResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => DisciplineRecordMetadata.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$DisciplineRecordMetadataResponseToJson(
  DisciplineRecordMetadataResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
