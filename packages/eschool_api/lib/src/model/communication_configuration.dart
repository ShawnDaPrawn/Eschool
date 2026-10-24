//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/communication_audience.dart';
import 'package:eschool_api/src/model/communication_configuration_years_inner.dart';
import 'package:eschool_api/src/model/communication_effective_rules.dart';
import 'package:eschool_api/src/model/communication_scope.dart';
import 'package:json_annotation/json_annotation.dart';

part 'communication_configuration.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommunicationConfiguration {
  /// Returns a new [CommunicationConfiguration] instance.
  CommunicationConfiguration({
    required this.schoolId,

    required this.readerId,

    required this.timezone,

    required this.staffAvailable,

    required this.canManage,

    required this.sessionYearId,

    required this.years,

    required this.scopes,

    required this.audiences,

    required this.rules,

    required this.channels,
  });

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  @JsonKey(name: r'timezone', required: true, includeIfNull: false)
  final String timezone;

  @JsonKey(name: r'staff_available', required: true, includeIfNull: false)
  final bool staffAvailable;

  @JsonKey(name: r'can_manage', required: true, includeIfNull: false)
  final bool canManage;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: true)
  final int? sessionYearId;

  @JsonKey(name: r'years', required: true, includeIfNull: false)
  final List<CommunicationConfigurationYearsInner> years;

  @JsonKey(name: r'scopes', required: true, includeIfNull: false)
  final List<CommunicationScope> scopes;

  @JsonKey(name: r'audiences', required: true, includeIfNull: false)
  final List<CommunicationAudience> audiences;

  @JsonKey(name: r'rules', required: true, includeIfNull: true)
  final CommunicationEffectiveRules? rules;

  @JsonKey(name: r'channels', required: true, includeIfNull: false)
  final List<String> channels;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommunicationConfiguration &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.timezone == timezone &&
          other.staffAvailable == staffAvailable &&
          other.canManage == canManage &&
          other.sessionYearId == sessionYearId &&
          other.years == years &&
          other.scopes == scopes &&
          other.audiences == audiences &&
          other.rules == rules &&
          other.channels == channels;

  @override
  int get hashCode =>
      schoolId.hashCode +
      readerId.hashCode +
      timezone.hashCode +
      staffAvailable.hashCode +
      canManage.hashCode +
      (sessionYearId == null ? 0 : sessionYearId.hashCode) +
      years.hashCode +
      scopes.hashCode +
      audiences.hashCode +
      (rules == null ? 0 : rules.hashCode) +
      channels.hashCode;

  factory CommunicationConfiguration.fromJson(Map<String, dynamic> json) =>
      _$CommunicationConfigurationFromJson(json);

  Map<String, dynamic> toJson() => _$CommunicationConfigurationToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
