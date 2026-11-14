//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/shared_library_contribution.dart';
import 'package:eschool_api/src/model/shared_library_contributions_sharing.dart';
import 'package:json_annotation/json_annotation.dart';

part 'shared_library_contributions.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SharedLibraryContributions {
  /// Returns a new [SharedLibraryContributions] instance.
  SharedLibraryContributions({
    required this.contributions,

    required this.sharing,
  });

  @JsonKey(name: r'contributions', required: true, includeIfNull: false)
  final List<SharedLibraryContribution> contributions;

  @JsonKey(name: r'sharing', required: true, includeIfNull: false)
  final SharedLibraryContributionsSharing sharing;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SharedLibraryContributions &&
          other.contributions == contributions &&
          other.sharing == sharing;

  @override
  int get hashCode => contributions.hashCode + sharing.hashCode;

  factory SharedLibraryContributions.fromJson(Map<String, dynamic> json) =>
      _$SharedLibraryContributionsFromJson(json);

  Map<String, dynamic> toJson() => _$SharedLibraryContributionsToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
