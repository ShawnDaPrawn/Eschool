//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/intervention_version.dart';
import 'package:json_annotation/json_annotation.dart';

part 'intervention_history.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class InterventionHistory {
  /// Returns a new [InterventionHistory] instance.
  InterventionHistory({required this.interventionId, required this.versions});

  // minimum: 1
  @JsonKey(name: r'intervention_id', required: true, includeIfNull: false)
  final int interventionId;

  @JsonKey(name: r'versions', required: true, includeIfNull: false)
  final List<InterventionVersion> versions;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is InterventionHistory &&
          other.interventionId == interventionId &&
          other.versions == versions;

  @override
  int get hashCode => interventionId.hashCode + versions.hashCode;

  factory InterventionHistory.fromJson(Map<String, dynamic> json) =>
      _$InterventionHistoryFromJson(json);

  Map<String, dynamic> toJson() => _$InterventionHistoryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
