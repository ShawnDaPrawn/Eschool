//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/vendor_operator_counter.dart';
import 'package:json_annotation/json_annotation.dart';

part 'vendor_operator_profile.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class VendorOperatorProfile {
  /// Returns a new [VendorOperatorProfile] instance.
  VendorOperatorProfile({
    required this.schoolId,

    required this.schoolName,

    required this.schoolSlug,

    required this.userId,

    required this.name,

    required this.counters,
  });

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  @JsonKey(name: r'school_name', required: true, includeIfNull: false)
  final String schoolName;

  @JsonKey(name: r'school_slug', required: true, includeIfNull: false)
  final String schoolSlug;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'user_id', required: true, includeIfNull: false)
  final int userId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'counters', required: true, includeIfNull: false)
  final List<VendorOperatorCounter> counters;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is VendorOperatorProfile &&
          other.schoolId == schoolId &&
          other.schoolName == schoolName &&
          other.schoolSlug == schoolSlug &&
          other.userId == userId &&
          other.name == name &&
          other.counters == counters;

  @override
  int get hashCode =>
      schoolId.hashCode +
      schoolName.hashCode +
      schoolSlug.hashCode +
      userId.hashCode +
      name.hashCode +
      counters.hashCode;

  factory VendorOperatorProfile.fromJson(Map<String, dynamic> json) =>
      _$VendorOperatorProfileFromJson(json);

  Map<String, dynamic> toJson() => _$VendorOperatorProfileToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
