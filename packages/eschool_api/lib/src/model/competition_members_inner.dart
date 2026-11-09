//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'competition_members_inner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CompetitionMembersInner {
  /// Returns a new [CompetitionMembersInner] instance.
  CompetitionMembersInner({
    required this.name,

    required this.role,

    required this.state,
  });

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'role', required: true, includeIfNull: false)
  final String role;

  @JsonKey(name: r'state', required: true, includeIfNull: false)
  final String state;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CompetitionMembersInner &&
          other.name == name &&
          other.role == role &&
          other.state == state;

  @override
  int get hashCode => name.hashCode + role.hashCode + state.hashCode;

  factory CompetitionMembersInner.fromJson(Map<String, dynamic> json) =>
      _$CompetitionMembersInnerFromJson(json);

  Map<String, dynamic> toJson() => _$CompetitionMembersInnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
