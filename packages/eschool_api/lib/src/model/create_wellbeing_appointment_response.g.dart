// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'create_wellbeing_appointment_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CreateWellbeingAppointmentResponse _$CreateWellbeingAppointmentResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CreateWellbeingAppointmentResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = CreateWellbeingAppointmentResponse(
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

Map<String, dynamic> _$CreateWellbeingAppointmentResponseToJson(
  CreateWellbeingAppointmentResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
