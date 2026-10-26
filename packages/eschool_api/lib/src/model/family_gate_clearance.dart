//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'family_gate_clearance.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FamilyGateClearance {
  /// Returns a new [FamilyGateClearance] instance.
  FamilyGateClearance({
    required this.id,

    required this.revision,

    required this.collector,

    required this.startsAt,

    required this.endsAt,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'collector', required: true, includeIfNull: false)
  final String collector;

  @JsonKey(name: r'starts_at', required: true, includeIfNull: false)
  final DateTime startsAt;

  @JsonKey(name: r'ends_at', required: true, includeIfNull: false)
  final DateTime endsAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilyGateClearance &&
          other.id == id &&
          other.revision == revision &&
          other.collector == collector &&
          other.startsAt == startsAt &&
          other.endsAt == endsAt;

  @override
  int get hashCode =>
      id.hashCode +
      revision.hashCode +
      collector.hashCode +
      startsAt.hashCode +
      endsAt.hashCode;

  factory FamilyGateClearance.fromJson(Map<String, dynamic> json) =>
      _$FamilyGateClearanceFromJson(json);

  Map<String, dynamic> toJson() => _$FamilyGateClearanceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
