//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'context_user.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ContextUser {
  /// Returns a new [ContextUser] instance.
  ContextUser({
    required this.id,

    required this.firstName,

    required this.lastName,

    required this.name,

    required this.email,

    required this.image,

    required this.roles,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'first_name', required: true, includeIfNull: false)
  final String firstName;

  @JsonKey(name: r'last_name', required: true, includeIfNull: false)
  final String lastName;

  /// First and last name.
  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  /// The sign-in name; a learner's is their GR number.
  @JsonKey(name: r'email', required: true, includeIfNull: false)
  final String email;

  /// The profile picture's URL, or null.
  @JsonKey(name: r'image', required: true, includeIfNull: true)
  final String? image;

  /// Role names, for example `Parent`, `Teacher`, `Super Admin` or a role the school created.
  @JsonKey(name: r'roles', required: true, includeIfNull: false)
  final List<String> roles;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ContextUser &&
          other.id == id &&
          other.firstName == firstName &&
          other.lastName == lastName &&
          other.name == name &&
          other.email == email &&
          other.image == image &&
          other.roles == roles;

  @override
  int get hashCode =>
      id.hashCode +
      firstName.hashCode +
      lastName.hashCode +
      name.hashCode +
      email.hashCode +
      (image == null ? 0 : image.hashCode) +
      roles.hashCode;

  factory ContextUser.fromJson(Map<String, dynamic> json) =>
      _$ContextUserFromJson(json);

  Map<String, dynamic> toJson() => _$ContextUserToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
