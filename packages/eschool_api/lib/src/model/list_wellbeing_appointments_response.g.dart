// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'list_wellbeing_appointments_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ListWellbeingAppointmentsResponse _$ListWellbeingAppointmentsResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ListWellbeingAppointmentsResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ListWellbeingAppointmentsResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => (v as List<dynamic>)
          .map((e) => WellbeingAppointment.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$ListWellbeingAppointmentsResponseToJson(
  ListWellbeingAppointmentsResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.map((e) => e.toJson()).toList(),
  'code': instance.code,
};
