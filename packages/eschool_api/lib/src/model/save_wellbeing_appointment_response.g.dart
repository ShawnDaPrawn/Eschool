// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'save_wellbeing_appointment_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SaveWellbeingAppointmentResponse _$SaveWellbeingAppointmentResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SaveWellbeingAppointmentResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = SaveWellbeingAppointmentResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => WellbeingAppointment.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$SaveWellbeingAppointmentResponseToJson(
  SaveWellbeingAppointmentResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
