//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'shared_library_contributions_sharing.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SharedLibraryContributionsSharing {
  /// Returns a new [SharedLibraryContributionsSharing] instance.
  SharedLibraryContributionsSharing({
    required this.sharingEnabled,

    required this.leaderApproval,

    required this.libraryEnabled,
  });

  @JsonKey(name: r'sharing_enabled', required: true, includeIfNull: false)
  final bool sharingEnabled;

  @JsonKey(name: r'leader_approval', required: true, includeIfNull: false)
  final bool leaderApproval;

  @JsonKey(name: r'library_enabled', required: true, includeIfNull: false)
  final bool libraryEnabled;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SharedLibraryContributionsSharing &&
          other.sharingEnabled == sharingEnabled &&
          other.leaderApproval == leaderApproval &&
          other.libraryEnabled == libraryEnabled;

  @override
  int get hashCode =>
      sharingEnabled.hashCode +
      leaderApproval.hashCode +
      libraryEnabled.hashCode;

  factory SharedLibraryContributionsSharing.fromJson(
    Map<String, dynamic> json,
  ) => _$SharedLibraryContributionsSharingFromJson(json);

  Map<String, dynamic> toJson() =>
      _$SharedLibraryContributionsSharingToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
