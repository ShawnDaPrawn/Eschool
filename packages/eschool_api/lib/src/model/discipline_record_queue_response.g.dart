// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_record_queue_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineRecordQueueResponse _$DisciplineRecordQueueResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('DisciplineRecordQueueResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = DisciplineRecordQueueResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => DisciplineRecordQueue.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$DisciplineRecordQueueResponseToJson(
  DisciplineRecordQueueResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
