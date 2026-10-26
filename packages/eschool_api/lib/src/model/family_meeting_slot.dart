//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'family_meeting_slot.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FamilyMeetingSlot {
  /// Returns a new [FamilyMeetingSlot] instance.
  FamilyMeetingSlot({
    required this.id,

    required this.hostId,

    required this.hostName,

    required this.title,

    required this.venue,

    required this.startsAt,

    required this.endsAt,

    required this.bookingClosesAt,

    required this.status,

    required this.revision,

    this.learnerId,

    this.learnerName,

    this.guardianUserId,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  // minimum: 1
  @JsonKey(name: r'host_id', required: true, includeIfNull: false)
  final int hostId;

  @JsonKey(name: r'host_name', required: true, includeIfNull: false)
  final String hostName;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'venue', required: true, includeIfNull: false)
  final String venue;

  @JsonKey(name: r'starts_at', required: true, includeIfNull: false)
  final DateTime startsAt;

  @JsonKey(name: r'ends_at', required: true, includeIfNull: false)
  final DateTime endsAt;

  @JsonKey(name: r'booking_closes_at', required: true, includeIfNull: false)
  final DateTime bookingClosesAt;

  @JsonKey(name: r'status', required: true, includeIfNull: false)
  final String status;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: false, includeIfNull: false)
  final int? learnerId;

  @JsonKey(name: r'learner_name', required: false, includeIfNull: false)
  final String? learnerName;

  // minimum: 1
  @JsonKey(name: r'guardian_user_id', required: false, includeIfNull: false)
  final int? guardianUserId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilyMeetingSlot &&
          other.id == id &&
          other.hostId == hostId &&
          other.hostName == hostName &&
          other.title == title &&
          other.venue == venue &&
          other.startsAt == startsAt &&
          other.endsAt == endsAt &&
          other.bookingClosesAt == bookingClosesAt &&
          other.status == status &&
          other.revision == revision &&
          other.learnerId == learnerId &&
          other.learnerName == learnerName &&
          other.guardianUserId == guardianUserId;

  @override
  int get hashCode =>
      id.hashCode +
      hostId.hashCode +
      hostName.hashCode +
      title.hashCode +
      venue.hashCode +
      startsAt.hashCode +
      endsAt.hashCode +
      bookingClosesAt.hashCode +
      status.hashCode +
      revision.hashCode +
      (learnerId == null ? 0 : learnerId.hashCode) +
      (learnerName == null ? 0 : learnerName.hashCode) +
      (guardianUserId == null ? 0 : guardianUserId.hashCode);

  factory FamilyMeetingSlot.fromJson(Map<String, dynamic> json) =>
      _$FamilyMeetingSlotFromJson(json);

  Map<String, dynamic> toJson() => _$FamilyMeetingSlotToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
