// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'admission_ticket_page_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AdmissionTicketPageResponse _$AdmissionTicketPageResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AdmissionTicketPageResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = AdmissionTicketPageResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => AdmissionTicketPage.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$AdmissionTicketPageResponseToJson(
  AdmissionTicketPageResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
