//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'foundation_learner_context.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FoundationLearnerContext {
  /// Returns a new [FoundationLearnerContext] instance.
  FoundationLearnerContext({
    required this.schoolId,

    required this.learnerId,

    required this.name,

    required this.teachingGroupId,

    required this.expiresAt,

    required this.restricted,

    required this.capabilities,
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

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FoundationLearnerContext &&
          other.schoolId == schoolId &&
          other.learnerId == learnerId &&
          other.name == name &&
          other.teachingGroupId == teachingGroupId &&
          other.expiresAt == expiresAt &&
          other.restricted == restricted &&
          other.capabilities == capabilities;

  @override
  int get hashCode =>
      schoolId.hashCode +
      learnerId.hashCode +
      name.hashCode +
      teachingGroupId.hashCode +
      expiresAt.hashCode +
      restricted.hashCode +
      capabilities.hashCode;

  factory FoundationLearnerContext.fromJson(Map<String, dynamic> json) =>
      _$FoundationLearnerContextFromJson(json);

  Map<String, dynamic> toJson() => _$FoundationLearnerContextToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
