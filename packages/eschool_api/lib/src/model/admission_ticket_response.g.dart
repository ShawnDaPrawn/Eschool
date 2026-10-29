// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'admission_ticket_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AdmissionTicketResponse _$AdmissionTicketResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AdmissionTicketResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = AdmissionTicketResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => AdmissionTicket.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$AdmissionTicketResponseToJson(
  AdmissionTicketResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
