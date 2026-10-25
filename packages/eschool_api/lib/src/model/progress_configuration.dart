//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/progress_band_settings.dart';
import 'package:eschool_api/src/model/progress_option.dart';
import 'package:eschool_api/src/model/progress_group.dart';
import 'package:json_annotation/json_annotation.dart';

part 'progress_configuration.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ProgressConfiguration {
  /// Returns a new [ProgressConfiguration] instance.
  ProgressConfiguration({
    required this.sessionYearId,

    required this.canManageBands,

    required this.canAssignCounselling,

    required this.canReadCaseload,

    required this.settings,

    required this.restrictedStaff,

    required this.years,

    required this.terms,

    required this.groups,
  });

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'can_manage_bands', required: true, includeIfNull: false)
  final bool canManageBands;

  @JsonKey(
    name: r'can_assign_counselling',
    required: true,
    includeIfNull: false,
  )
  final bool canAssignCounselling;

  @JsonKey(name: r'can_read_caseload', required: true, includeIfNull: false)
  final bool canReadCaseload;

  @JsonKey(name: r'settings', required: true, includeIfNull: false)
  final ProgressBandSettings settings;

  @JsonKey(name: r'restricted_staff', required: true, includeIfNull: false)
  final List<ProgressOption> restrictedStaff;

  @JsonKey(name: r'years', required: true, includeIfNull: false)
  final List<ProgressOption> years;

  @JsonKey(name: r'terms', required: true, includeIfNull: false)
  final List<ProgressOption> terms;

  @JsonKey(name: r'groups', required: true, includeIfNull: false)
  final List<ProgressGroup> groups;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ProgressConfiguration &&
          other.sessionYearId == sessionYearId &&
          other.canManageBands == canManageBands &&
          other.canAssignCounselling == canAssignCounselling &&
          other.canReadCaseload == canReadCaseload &&
          other.settings == settings &&
          other.restrictedStaff == restrictedStaff &&
          other.years == years &&
          other.terms == terms &&
          other.groups == groups;

  @override
  int get hashCode =>
      sessionYearId.hashCode +
      canManageBands.hashCode +
      canAssignCounselling.hashCode +
      canReadCaseload.hashCode +
      settings.hashCode +
      restrictedStaff.hashCode +
      years.hashCode +
      terms.hashCode +
      groups.hashCode;

  factory ProgressConfiguration.fromJson(Map<String, dynamic> json) =>
      _$ProgressConfigurationFromJson(json);

  Map<String, dynamic> toJson() => _$ProgressConfigurationToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
