//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'support_assignment.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SupportAssignment {
  /// Returns a new [SupportAssignment] instance.
  SupportAssignment({
    required this.id,

    required this.learnerId,

    required this.learnerName,

    required this.userId,

    required this.professionalName,

    required this.startsOn,

    required this.endsOn,

    required this.active,

    required this.revision,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'learner_name', required: true, includeIfNull: false)
  final String learnerName;

  // minimum: 1
  @JsonKey(name: r'user_id', required: true, includeIfNull: false)
  final int userId;

  @JsonKey(name: r'professional_name', required: true, includeIfNull: false)
  final String professionalName;

  @JsonKey(name: r'starts_on', required: true, includeIfNull: false)
  final String startsOn;

  @JsonKey(name: r'ends_on', required: true, includeIfNull: false)
  final String endsOn;

  @JsonKey(name: r'active', required: true, includeIfNull: false)
  final bool active;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SupportAssignment &&
          other.id == id &&
          other.learnerId == learnerId &&
          other.learnerName == learnerName &&
          other.userId == userId &&
          other.professionalName == professionalName &&
          other.startsOn == startsOn &&
          other.endsOn == endsOn &&
          other.active == active &&
          other.revision == revision;

  @override
  int get hashCode =>
      id.hashCode +
      learnerId.hashCode +
      learnerName.hashCode +
      userId.hashCode +
      professionalName.hashCode +
      startsOn.hashCode +
      endsOn.hashCode +
      active.hashCode +
      revision.hashCode;

  factory SupportAssignment.fromJson(Map<String, dynamic> json) =>
      _$SupportAssignmentFromJson(json);

  Map<String, dynamic> toJson() => _$SupportAssignmentToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
