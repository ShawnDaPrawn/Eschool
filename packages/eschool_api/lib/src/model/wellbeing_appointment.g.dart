// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wellbeing_appointment.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WellbeingAppointment _$WellbeingAppointmentFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WellbeingAppointment',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'support_case_id',
        'professional_user_id',
        'starts_at',
        'ends_at',
        'location',
        'notes',
        'status',
        'revision',
      ],
    );
    final val = WellbeingAppointment(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      supportCaseId: $checkedConvert(
        'support_case_id',
        (v) => (v as num).toInt(),
      ),
      professionalUserId: $checkedConvert(
        'professional_user_id',
        (v) => (v as num).toInt(),
      ),
      startsAt: $checkedConvert(
        'starts_at',
        (v) => DateTime.parse(v as String),
      ),
      endsAt: $checkedConvert('ends_at', (v) => DateTime.parse(v as String)),
      location: $checkedConvert('location', (v) => v as String),
      notes: $checkedConvert('notes', (v) => v as String),
      status: $checkedConvert(
        'status',
        (v) => $enumDecode(
          _$WellbeingAppointmentStatusEnumEnumMap,
          v,
          unknownValue: WellbeingAppointmentStatusEnum.unknownDefaultOpenApi,
        ),
      ),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'supportCaseId': 'support_case_id',
    'professionalUserId': 'professional_user_id',
    'startsAt': 'starts_at',
    'endsAt': 'ends_at',
  },
);

Map<String, dynamic> _$WellbeingAppointmentToJson(
  WellbeingAppointment instance,
) => <String, dynamic>{
  'id': instance.id,
  'support_case_id': instance.supportCaseId,
  'professional_user_id': instance.professionalUserId,
  'starts_at': instance.startsAt.toIso8601String(),
  'ends_at': instance.endsAt.toIso8601String(),
  'location': instance.location,
  'notes': instance.notes,
  'status': _$WellbeingAppointmentStatusEnumEnumMap[instance.status]!,
  'revision': instance.revision,
};

const _$WellbeingAppointmentStatusEnumEnumMap = {
  WellbeingAppointmentStatusEnum.scheduled: 'scheduled',
  WellbeingAppointmentStatusEnum.completed: 'completed',
  WellbeingAppointmentStatusEnum.cancelled: 'cancelled',
  WellbeingAppointmentStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
