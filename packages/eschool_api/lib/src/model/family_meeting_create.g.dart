// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_meeting_create.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyMeetingCreate _$FamilyMeetingCreateFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'FamilyMeetingCreate',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'operation_id',
            'host_id',
            'title',
            'venue',
            'starts_at',
            'ends_at',
            'booking_closes_at',
          ],
        );
        final val = FamilyMeetingCreate(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          hostId: $checkedConvert('host_id', (v) => (v as num).toInt()),
          title: $checkedConvert('title', (v) => v as String),
          venue: $checkedConvert('venue', (v) => v as String),
          startsAt: $checkedConvert('starts_at', (v) => v as String),
          endsAt: $checkedConvert('ends_at', (v) => v as String),
          bookingClosesAt: $checkedConvert(
            'booking_closes_at',
            (v) => v as String,
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'hostId': 'host_id',
        'startsAt': 'starts_at',
        'endsAt': 'ends_at',
        'bookingClosesAt': 'booking_closes_at',
      },
    );

Map<String, dynamic> _$FamilyMeetingCreateToJson(
  FamilyMeetingCreate instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'host_id': instance.hostId,
  'title': instance.title,
  'venue': instance.venue,
  'starts_at': instance.startsAt,
  'ends_at': instance.endsAt,
  'booking_closes_at': instance.bookingClosesAt,
};
