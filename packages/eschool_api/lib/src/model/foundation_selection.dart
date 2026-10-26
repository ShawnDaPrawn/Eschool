//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'foundation_selection.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FoundationSelection {
  /// Returns a new [FoundationSelection] instance.
  FoundationSelection({
    required this.schoolId,

    required this.learnerId,

    required this.name,

    required this.teachingGroupId,

    required this.expiresAt,

    required this.restricted,

    required this.capabilities,

    required this.classroomSession,
  });

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  // minimum: 1
  @JsonKey(name: r'teaching_group_id', required: true, includeIfNull: false)
  final int teachingGroupId;

  @JsonKey(name: r'expires_at', required: true, includeIfNull: false)
  final DateTime expiresAt;

  /// Always true. No ordinary account or gate authority.
  @JsonKey(name: r'restricted', required: true, includeIfNull: false)
  final bool restricted;

  @JsonKey(name: r'capabilities', required: true, includeIfNull: false)
  final List<String> capabilities;

  /// Private short learner credential; subsequent learner requests combine this with the same device credential in one Authorization Bearer value separated by ~.
  @JsonKey(name: r'classroom_session', required: true, includeIfNull: false)
  final String classroomSession;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FoundationSelection &&
          other.schoolId == schoolId &&
          other.learnerId == learnerId &&
          other.name == name &&
          other.teachingGroupId == teachingGroupId &&
          other.expiresAt == expiresAt &&
          other.restricted == restricted &&
          other.capabilities == capabilities &&
          other.classroomSession == classroomSession;

  @override
  int get hashCode =>
      schoolId.hashCode +
      learnerId.hashCode +
      name.hashCode +
      teachingGroupId.hashCode +
      expiresAt.hashCode +
      restricted.hashCode +
      capabilities.hashCode +
      classroomSession.hashCode;

  factory FoundationSelection.fromJson(Map<String, dynamic> json) =>
      _$FoundationSelectionFromJson(json);

  Map<String, dynamic> toJson() => _$FoundationSelectionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
