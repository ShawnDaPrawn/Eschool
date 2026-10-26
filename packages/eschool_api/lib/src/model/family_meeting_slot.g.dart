// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_meeting_slot.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyMeetingSlot _$FamilyMeetingSlotFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'FamilyMeetingSlot',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'host_id',
            'host_name',
            'title',
            'venue',
            'starts_at',
            'ends_at',
            'booking_closes_at',
            'status',
            'revision',
          ],
        );
        final val = FamilyMeetingSlot(
          id: $checkedConvert('id', (v) => v as String),
          hostId: $checkedConvert('host_id', (v) => (v as num).toInt()),
          hostName: $checkedConvert('host_name', (v) => v as String),
          title: $checkedConvert('title', (v) => v as String),
          venue: $checkedConvert('venue', (v) => v as String),
          startsAt: $checkedConvert(
            'starts_at',
            (v) => DateTime.parse(v as String),
          ),
          endsAt: $checkedConvert(
            'ends_at',
            (v) => DateTime.parse(v as String),
          ),
          bookingClosesAt: $checkedConvert(
            'booking_closes_at',
            (v) => DateTime.parse(v as String),
          ),
          status: $checkedConvert('status', (v) => v as String),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
          learnerId: $checkedConvert('learner_id', (v) => (v as num?)?.toInt()),
          learnerName: $checkedConvert('learner_name', (v) => v as String?),
          guardianUserId: $checkedConvert(
            'guardian_user_id',
            (v) => (v as num?)?.toInt(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'hostId': 'host_id',
        'hostName': 'host_name',
        'startsAt': 'starts_at',
        'endsAt': 'ends_at',
        'bookingClosesAt': 'booking_closes_at',
        'learnerId': 'learner_id',
        'learnerName': 'learner_name',
        'guardianUserId': 'guardian_user_id',
      },
    );

Map<String, dynamic> _$FamilyMeetingSlotToJson(FamilyMeetingSlot instance) =>
    <String, dynamic>{
      'id': instance.id,
      'host_id': instance.hostId,
      'host_name': instance.hostName,
      'title': instance.title,
      'venue': instance.venue,
      'starts_at': instance.startsAt.toIso8601String(),
      'ends_at': instance.endsAt.toIso8601String(),
      'booking_closes_at': instance.bookingClosesAt.toIso8601String(),
      'status': instance.status,
      'revision': instance.revision,
      'learner_id': ?instance.learnerId,
      'learner_name': ?instance.learnerName,
      'guardian_user_id': ?instance.guardianUserId,
    };
