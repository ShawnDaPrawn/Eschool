//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'family_gate_collected.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FamilyGateCollected {
  /// Returns a new [FamilyGateCollected] instance.
  FamilyGateCollected({
    required this.id,

    required this.status,

    required this.revision,

    required this.collectedAt,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @JsonKey(name: r'status', required: true, includeIfNull: false)
  final String status;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'collected_at', required: true, includeIfNull: false)
  final DateTime collectedAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilyGateCollected &&
          other.id == id &&
          other.status == status &&
          other.revision == revision &&
          other.collectedAt == collectedAt;

  @override
  int get hashCode =>
      id.hashCode + status.hashCode + revision.hashCode + collectedAt.hashCode;

  factory FamilyGateCollected.fromJson(Map<String, dynamic> json) =>
      _$FamilyGateCollectedFromJson(json);

  Map<String, dynamic> toJson() => _$FamilyGateCollectedToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
