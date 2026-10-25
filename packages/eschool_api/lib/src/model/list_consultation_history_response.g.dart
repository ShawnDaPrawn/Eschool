// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'list_consultation_history_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ListConsultationHistoryResponse _$ListConsultationHistoryResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ListConsultationHistoryResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ListConsultationHistoryResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => ConsultationHistoryPage.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$ListConsultationHistoryResponseToJson(
  ListConsultationHistoryResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
