//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/facilitator_school.dart';
import 'package:eschool_api/src/model/facilitator_features.dart';
import 'package:json_annotation/json_annotation.dart';

part 'facilitator_profile.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FacilitatorProfile {
  /// Returns a new [FacilitatorProfile] instance.
  FacilitatorProfile({
    required this.userId,

    required this.schoolId,

    required this.name,

    required this.roles,

    required this.schools,

    required this.features,
  });

  // minimum: 1
  @JsonKey(name: r'user_id', required: true, includeIfNull: false)
  final int userId;

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'roles', required: true, includeIfNull: false)
  final List<String> roles;

  @JsonKey(name: r'schools', required: true, includeIfNull: false)
  final List<FacilitatorSchool> schools;

  @JsonKey(name: r'features', required: true, includeIfNull: false)
  final FacilitatorFeatures features;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FacilitatorProfile &&
          other.userId == userId &&
          other.schoolId == schoolId &&
          other.name == name &&
          other.roles == roles &&
          other.schools == schools &&
          other.features == features;

  @override
  int get hashCode =>
      userId.hashCode +
      schoolId.hashCode +
      name.hashCode +
      roles.hashCode +
      schools.hashCode +
      features.hashCode;

  factory FacilitatorProfile.fromJson(Map<String, dynamic> json) =>
      _$FacilitatorProfileFromJson(json);

  Map<String, dynamic> toJson() => _$FacilitatorProfileToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
