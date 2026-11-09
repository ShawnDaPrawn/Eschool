//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/learner_primary_identifier.dart';
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

    this.primaryIdentifier,
  });

  @JsonKey(name: r'person_id', required: true, includeIfNull: false)
  final int personId;

  @JsonKey(name: r'profile_type', required: true, includeIfNull: false)
  final String profileType;

  @JsonKey(name: r'available_profiles', required: true, includeIfNull: false)
  final List<String> availableProfiles;

  @JsonKey(name: r'categories', required: true, includeIfNull: false)
  final List<ProfileFormCategory> categories;

  /// For a learner profile, the learner's headline number (ADR 0007), the LURITS number or else the temporary NavigateEd number. Null when the caller may not see it (only the learner, a guardian who may approve, and staff with profile permission may) for other profile types, or when the learner has neither.
  @JsonKey(name: r'primary_identifier', required: false, includeIfNull: false)
  final LearnerPrimaryIdentifier? primaryIdentifier;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ProfileForm &&
          other.personId == personId &&
          other.profileType == profileType &&
          other.availableProfiles == availableProfiles &&
          other.categories == categories &&
          other.primaryIdentifier == primaryIdentifier;

  @override
  int get hashCode =>
      personId.hashCode +
      profileType.hashCode +
      availableProfiles.hashCode +
      categories.hashCode +
      (primaryIdentifier == null ? 0 : primaryIdentifier.hashCode);

  factory ProfileForm.fromJson(Map<String, dynamic> json) =>
      _$ProfileFormFromJson(json);

  Map<String, dynamic> toJson() => _$ProfileFormToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
