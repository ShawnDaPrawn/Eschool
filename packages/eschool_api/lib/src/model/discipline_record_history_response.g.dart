// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_record_history_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineRecordHistoryResponse _$DisciplineRecordHistoryResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('DisciplineRecordHistoryResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = DisciplineRecordHistoryResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => DisciplineRecordHistory.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$DisciplineRecordHistoryResponseToJson(
  DisciplineRecordHistoryResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
