//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'family_visit_rules.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FamilyVisitRules {
  /// Returns a new [FamilyVisitRules] instance.
  FamilyVisitRules({
    required this.maxWindowMinutes,

    required this.maxFutureDays,

    required this.scanMaxAgeSeconds,

    required this.revision,

    required this.staffApprovalRequired,

    required this.collectorCheckRequired,

    this.id,
  });

  // minimum: 15
  // maximum: 240
  @JsonKey(name: r'max_window_minutes', required: true, includeIfNull: false)
  final int maxWindowMinutes;

  // minimum: 1
  // maximum: 30
  @JsonKey(name: r'max_future_days', required: true, includeIfNull: false)
  final int maxFutureDays;

  // minimum: 15
  // maximum: 120
  @JsonKey(name: r'scan_max_age_seconds', required: true, includeIfNull: false)
  final int scanMaxAgeSeconds;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(
    name: r'staff_approval_required',
    required: true,
    includeIfNull: false,
  )
  final bool staffApprovalRequired;

  @JsonKey(
    name: r'collector_check_required',
    required: true,
    includeIfNull: false,
  )
  final bool collectorCheckRequired;

  @JsonKey(name: r'id', required: false, includeIfNull: false)
  final String? id;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilyVisitRules &&
          other.maxWindowMinutes == maxWindowMinutes &&
          other.maxFutureDays == maxFutureDays &&
          other.scanMaxAgeSeconds == scanMaxAgeSeconds &&
          other.revision == revision &&
          other.staffApprovalRequired == staffApprovalRequired &&
          other.collectorCheckRequired == collectorCheckRequired &&
          other.id == id;

  @override
  int get hashCode =>
      maxWindowMinutes.hashCode +
      maxFutureDays.hashCode +
      scanMaxAgeSeconds.hashCode +
      revision.hashCode +
      staffApprovalRequired.hashCode +
      collectorCheckRequired.hashCode +
      id.hashCode;

  factory FamilyVisitRules.fromJson(Map<String, dynamic> json) =>
      _$FamilyVisitRulesFromJson(json);

  Map<String, dynamic> toJson() => _$FamilyVisitRulesToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
