//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'family_meeting_booking.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FamilyMeetingBooking {
  /// Returns a new [FamilyMeetingBooking] instance.
  FamilyMeetingBooking({
    required this.operationId,

    required this.revision,

    required this.learnerId,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilyMeetingBooking &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.learnerId == learnerId;

  @override
  int get hashCode =>
      operationId.hashCode + revision.hashCode + learnerId.hashCode;

  factory FamilyMeetingBooking.fromJson(Map<String, dynamic> json) =>
      _$FamilyMeetingBookingFromJson(json);

  Map<String, dynamic> toJson() => _$FamilyMeetingBookingToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
