// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_meeting_booking.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyMeetingBooking _$FamilyMeetingBookingFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'FamilyMeetingBooking',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['operation_id', 'revision', 'learner_id'],
    );
    final val = FamilyMeetingBooking(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {'operationId': 'operation_id', 'learnerId': 'learner_id'},
);

Map<String, dynamic> _$FamilyMeetingBookingToJson(
  FamilyMeetingBooking instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'revision': instance.revision,
  'learner_id': instance.learnerId,
};
