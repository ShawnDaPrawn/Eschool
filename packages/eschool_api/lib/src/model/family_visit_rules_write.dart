//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'family_visit_rules_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FamilyVisitRulesWrite {
  /// Returns a new [FamilyVisitRulesWrite] instance.
  FamilyVisitRulesWrite({
    required this.operationId,

    required this.revision,

    required this.maxWindowMinutes,

    required this.maxFutureDays,

    required this.scanMaxAgeSeconds,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

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

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilyVisitRulesWrite &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.maxWindowMinutes == maxWindowMinutes &&
          other.maxFutureDays == maxFutureDays &&
          other.scanMaxAgeSeconds == scanMaxAgeSeconds;

  @override
  int get hashCode =>
      operationId.hashCode +
      revision.hashCode +
      maxWindowMinutes.hashCode +
      maxFutureDays.hashCode +
      scanMaxAgeSeconds.hashCode;

  factory FamilyVisitRulesWrite.fromJson(Map<String, dynamic> json) =>
      _$FamilyVisitRulesWriteFromJson(json);

  Map<String, dynamic> toJson() => _$FamilyVisitRulesWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
