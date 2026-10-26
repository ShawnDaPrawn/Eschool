//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'family_meeting_create.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FamilyMeetingCreate {
  /// Returns a new [FamilyMeetingCreate] instance.
  FamilyMeetingCreate({
    required this.operationId,

    required this.hostId,

    required this.title,

    required this.venue,

    required this.startsAt,

    required this.endsAt,

    required this.bookingClosesAt,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'host_id', required: true, includeIfNull: false)
  final int hostId;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'venue', required: true, includeIfNull: false)
  final String venue;

  @JsonKey(name: r'starts_at', required: true, includeIfNull: false)
  final String startsAt;

  @JsonKey(name: r'ends_at', required: true, includeIfNull: false)
  final String endsAt;

  @JsonKey(name: r'booking_closes_at', required: true, includeIfNull: false)
  final String bookingClosesAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilyMeetingCreate &&
          other.operationId == operationId &&
          other.hostId == hostId &&
          other.title == title &&
          other.venue == venue &&
          other.startsAt == startsAt &&
          other.endsAt == endsAt &&
          other.bookingClosesAt == bookingClosesAt;

  @override
  int get hashCode =>
      operationId.hashCode +
      hostId.hashCode +
      title.hashCode +
      venue.hashCode +
      startsAt.hashCode +
      endsAt.hashCode +
      bookingClosesAt.hashCode;

  factory FamilyMeetingCreate.fromJson(Map<String, dynamic> json) =>
      _$FamilyMeetingCreateFromJson(json);

  Map<String, dynamic> toJson() => _$FamilyMeetingCreateToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
