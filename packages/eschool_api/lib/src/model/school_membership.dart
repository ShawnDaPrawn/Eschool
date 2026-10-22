//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/school_summary.dart';
import 'package:json_annotation/json_annotation.dart';

part 'school_membership.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SchoolMembership {
  /// Returns a new [SchoolMembership] instance.
  SchoolMembership({required this.school, required this.roles});

  @JsonKey(name: r'school', required: true, includeIfNull: false)
  final SchoolSummary school;

  @JsonKey(name: r'roles', required: true, includeIfNull: false)
  final List<String> roles;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SchoolMembership &&
          other.school == school &&
          other.roles == roles;

  @override
  int get hashCode => school.hashCode + roles.hashCode;

  factory SchoolMembership.fromJson(Map<String, dynamic> json) =>
      _$SchoolMembershipFromJson(json);

  Map<String, dynamic> toJson() => _$SchoolMembershipToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
