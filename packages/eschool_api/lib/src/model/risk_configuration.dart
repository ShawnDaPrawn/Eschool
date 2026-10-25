//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/risk_settings.dart';
import 'package:eschool_api/src/model/progress_option.dart';
import 'package:eschool_api/src/model/progress_group.dart';
import 'package:json_annotation/json_annotation.dart';

part 'risk_configuration.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RiskConfiguration {
  /// Returns a new [RiskConfiguration] instance.
  RiskConfiguration({
    required this.sessionYearId,

    required this.canManage,

    required this.canReadSignals,

    required this.gradeIds,

    required this.groups,

    required this.settings,

    required this.years,

    this.today,
  });

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'can_manage', required: true, includeIfNull: false)
  final bool canManage;

  @JsonKey(name: r'can_read_signals', required: true, includeIfNull: false)
  final bool canReadSignals;

  @JsonKey(name: r'grade_ids', required: true, includeIfNull: false)
  final List<int> gradeIds;

  @JsonKey(name: r'groups', required: true, includeIfNull: false)
  final List<ProgressGroup> groups;

  @JsonKey(name: r'settings', required: true, includeIfNull: false)
  final RiskSettings settings;

  @JsonKey(name: r'years', required: true, includeIfNull: false)
  final List<ProgressOption> years;

  @JsonKey(name: r'today', required: false, includeIfNull: false)
  final String? today;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RiskConfiguration &&
          other.sessionYearId == sessionYearId &&
          other.canManage == canManage &&
          other.canReadSignals == canReadSignals &&
          other.gradeIds == gradeIds &&
          other.groups == groups &&
          other.settings == settings &&
          other.years == years &&
          other.today == today;

  @override
  int get hashCode =>
      sessionYearId.hashCode +
      canManage.hashCode +
      canReadSignals.hashCode +
      gradeIds.hashCode +
      groups.hashCode +
      settings.hashCode +
      years.hashCode +
      today.hashCode;

  factory RiskConfiguration.fromJson(Map<String, dynamic> json) =>
      _$RiskConfigurationFromJson(json);

  Map<String, dynamic> toJson() => _$RiskConfigurationToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
