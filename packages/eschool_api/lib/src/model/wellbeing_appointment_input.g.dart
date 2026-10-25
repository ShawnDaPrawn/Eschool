// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wellbeing_appointment_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WellbeingAppointmentInput _$WellbeingAppointmentInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WellbeingAppointmentInput',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'revision',
        'professional_user_id',
        'starts_at',
        'ends_at',
        'location',
        'notes',
        'status',
      ],
    );
    final val = WellbeingAppointmentInput(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
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
      notes: $checkedConvert('notes', (v) => v as String?),
      status: $checkedConvert(
        'status',
        (v) => $enumDecode(
          _$WellbeingAppointmentInputStatusEnumEnumMap,
          v,
          unknownValue:
              WellbeingAppointmentInputStatusEnum.unknownDefaultOpenApi,
        ),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'professionalUserId': 'professional_user_id',
    'startsAt': 'starts_at',
    'endsAt': 'ends_at',
  },
);

Map<String, dynamic> _$WellbeingAppointmentInputToJson(
  WellbeingAppointmentInput instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'revision': instance.revision,
  'professional_user_id': instance.professionalUserId,
  'starts_at': instance.startsAt.toIso8601String(),
  'ends_at': instance.endsAt.toIso8601String(),
  'location': instance.location,
  'notes': instance.notes,
  'status': _$WellbeingAppointmentInputStatusEnumEnumMap[instance.status]!,
};

const _$WellbeingAppointmentInputStatusEnumEnumMap = {
  WellbeingAppointmentInputStatusEnum.scheduled: 'scheduled',
  WellbeingAppointmentInputStatusEnum.completed: 'completed',
  WellbeingAppointmentInputStatusEnum.cancelled: 'cancelled',
  WellbeingAppointmentInputStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
