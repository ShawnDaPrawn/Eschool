//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/school_summary.dart';
import 'package:json_annotation/json_annotation.dart';

part 'child.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class Child {
  /// Returns a new [Child] instance.
  Child({
    required this.id,

    required this.userId,

    required this.firstName,

    required this.lastName,

    required this.name,

    required this.image,

    required this.school,
  });

  /// The learner's id (the `child_id` the v1 parent endpoints take).
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'user_id', required: true, includeIfNull: false)
  final int userId;

  @JsonKey(name: r'first_name', required: true, includeIfNull: false)
  final String firstName;

  @JsonKey(name: r'last_name', required: true, includeIfNull: false)
  final String lastName;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'image', required: true, includeIfNull: true)
  final String? image;

  @JsonKey(name: r'school', required: true, includeIfNull: false)
  final SchoolSummary school;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is Child &&
          other.id == id &&
          other.userId == userId &&
          other.firstName == firstName &&
          other.lastName == lastName &&
          other.name == name &&
          other.image == image &&
          other.school == school;

  @override
  int get hashCode =>
      id.hashCode +
      userId.hashCode +
      firstName.hashCode +
      lastName.hashCode +
      name.hashCode +
      (image == null ? 0 : image.hashCode) +
      school.hashCode;

  factory Child.fromJson(Map<String, dynamic> json) => _$ChildFromJson(json);

  Map<String, dynamic> toJson() => _$ChildToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
