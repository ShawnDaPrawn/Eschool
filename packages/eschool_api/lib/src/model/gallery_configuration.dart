//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/gallery_child.dart';
import 'package:eschool_api/src/model/gallery_group.dart';
import 'package:eschool_api/src/model/gallery_year.dart';
import 'package:json_annotation/json_annotation.dart';

part 'gallery_configuration.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GalleryConfiguration {
  /// Returns a new [GalleryConfiguration] instance.
  GalleryConfiguration({
    required this.schoolId,

    required this.accountId,

    required this.sessionYearId,

    required this.today,

    required this.years,

    required this.children,

    required this.groups,

    required this.guardianRequired,

    required this.settingsRevision,

    required this.canManage,

    required this.canWrite,
  });

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'account_id', required: true, includeIfNull: false)
  final int accountId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'today', required: true, includeIfNull: false)
  final String today;

  @JsonKey(name: r'years', required: true, includeIfNull: false)
  final List<GalleryYear> years;

  @JsonKey(name: r'children', required: true, includeIfNull: false)
  final List<GalleryChild> children;

  @JsonKey(name: r'groups', required: true, includeIfNull: false)
  final List<GalleryGroup> groups;

  @JsonKey(name: r'guardian_required', required: true, includeIfNull: false)
  final bool guardianRequired;

  // minimum: 0
  @JsonKey(name: r'settings_revision', required: true, includeIfNull: false)
  final int settingsRevision;

  @JsonKey(name: r'can_manage', required: true, includeIfNull: false)
  final bool canManage;

  @JsonKey(name: r'can_write', required: true, includeIfNull: false)
  final bool canWrite;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GalleryConfiguration &&
          other.schoolId == schoolId &&
          other.accountId == accountId &&
          other.sessionYearId == sessionYearId &&
          other.today == today &&
          other.years == years &&
          other.children == children &&
          other.groups == groups &&
          other.guardianRequired == guardianRequired &&
          other.settingsRevision == settingsRevision &&
          other.canManage == canManage &&
          other.canWrite == canWrite;

  @override
  int get hashCode =>
      schoolId.hashCode +
      accountId.hashCode +
      sessionYearId.hashCode +
      today.hashCode +
      years.hashCode +
      children.hashCode +
      groups.hashCode +
      guardianRequired.hashCode +
      settingsRevision.hashCode +
      canManage.hashCode +
      canWrite.hashCode;

  factory GalleryConfiguration.fromJson(Map<String, dynamic> json) =>
      _$GalleryConfigurationFromJson(json);

  Map<String, dynamic> toJson() => _$GalleryConfigurationToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
