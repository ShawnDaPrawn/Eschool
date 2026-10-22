//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/branding.dart';
import 'package:eschool_api/src/model/session_year.dart';
import 'package:json_annotation/json_annotation.dart';

part 'current_school.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CurrentSchool {
  /// Returns a new [CurrentSchool] instance.
  CurrentSchool({
    required this.id,

    required this.name,

    required this.shortName,

    required this.crestUrl,

    required this.tagline,

    required this.branding,

    required this.timezone,

    required this.sessionYear,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'short_name', required: true, includeIfNull: true)
  final String? shortName;

  @JsonKey(name: r'crest_url', required: true, includeIfNull: true)
  final String? crestUrl;

  @JsonKey(name: r'tagline', required: true, includeIfNull: true)
  final String? tagline;

  @JsonKey(name: r'branding', required: true, includeIfNull: false)
  final Branding branding;

  /// IANA timezone the school's dates and times are shown in, for example `Africa/Johannesburg`.
  @JsonKey(name: r'timezone', required: true, includeIfNull: false)
  final String timezone;

  /// The current school year, or null when none is set.
  @JsonKey(name: r'session_year', required: true, includeIfNull: true)
  final SessionYear? sessionYear;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CurrentSchool &&
          other.id == id &&
          other.name == name &&
          other.shortName == shortName &&
          other.crestUrl == crestUrl &&
          other.tagline == tagline &&
          other.branding == branding &&
          other.timezone == timezone &&
          other.sessionYear == sessionYear;

  @override
  int get hashCode =>
      id.hashCode +
      name.hashCode +
      (shortName == null ? 0 : shortName.hashCode) +
      (crestUrl == null ? 0 : crestUrl.hashCode) +
      (tagline == null ? 0 : tagline.hashCode) +
      branding.hashCode +
      timezone.hashCode +
      (sessionYear == null ? 0 : sessionYear.hashCode);

  factory CurrentSchool.fromJson(Map<String, dynamic> json) =>
      _$CurrentSchoolFromJson(json);

  Map<String, dynamic> toJson() => _$CurrentSchoolToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
