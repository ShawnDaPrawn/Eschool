// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'get_consultation_history_record_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GetConsultationHistoryRecordResponse
_$GetConsultationHistoryRecordResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('GetConsultationHistoryRecordResponse', json, (
      $checkedConvert,
    ) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'data', 'code'],
      );
      final val = GetConsultationHistoryRecordResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        data: $checkedConvert(
          'data',
          (v) => ConsultationHistoryRecord.fromJson(v as Map<String, dynamic>),
        ),
        code: $checkedConvert('code', (v) => (v as num).toInt()),
      );
      return val;
    });

Map<String, dynamic> _$GetConsultationHistoryRecordResponseToJson(
  GetConsultationHistoryRecordResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
