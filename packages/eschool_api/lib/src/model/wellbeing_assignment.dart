//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'wellbeing_assignment.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WellbeingAssignment {
  /// Returns a new [WellbeingAssignment] instance.
  WellbeingAssignment({
    required this.id,

    required this.userId,

    required this.name,

    required this.startsOn,

    required this.endsOn,

    required this.active,

    required this.revision,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'user_id', required: true, includeIfNull: false)
  final int userId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

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
      other is WellbeingAssignment &&
          other.id == id &&
          other.userId == userId &&
          other.name == name &&
          other.startsOn == startsOn &&
          other.endsOn == endsOn &&
          other.active == active &&
          other.revision == revision;

  @override
  int get hashCode =>
      id.hashCode +
      userId.hashCode +
      name.hashCode +
      startsOn.hashCode +
      endsOn.hashCode +
      active.hashCode +
      revision.hashCode;

  factory WellbeingAssignment.fromJson(Map<String, dynamic> json) =>
      _$WellbeingAssignmentFromJson(json);

  Map<String, dynamic> toJson() => _$WellbeingAssignmentToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
