//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/profile_form_category.dart';
import 'package:json_annotation/json_annotation.dart';

part 'profile_form.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ProfileForm {
  /// Returns a new [ProfileForm] instance.
  ProfileForm({
    required this.personId,

    required this.profileType,

    required this.availableProfiles,

    required this.categories,
  });

  @JsonKey(name: r'person_id', required: true, includeIfNull: false)
  final int personId;

  @JsonKey(name: r'profile_type', required: true, includeIfNull: false)
  final String profileType;

  @JsonKey(name: r'available_profiles', required: true, includeIfNull: false)
  final List<String> availableProfiles;

  @JsonKey(name: r'categories', required: true, includeIfNull: false)
  final List<ProfileFormCategory> categories;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ProfileForm &&
          other.personId == personId &&
          other.profileType == profileType &&
          other.availableProfiles == availableProfiles &&
          other.categories == categories;

  @override
  int get hashCode =>
      personId.hashCode +
      profileType.hashCode +
      availableProfiles.hashCode +
      categories.hashCode;

  factory ProfileForm.fromJson(Map<String, dynamic> json) =>
      _$ProfileFormFromJson(json);

  Map<String, dynamic> toJson() => _$ProfileFormToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
