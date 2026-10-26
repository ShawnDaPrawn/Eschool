//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'family_gate_collection.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FamilyGateCollection {
  /// Returns a new [FamilyGateCollection] instance.
  FamilyGateCollection({
    required this.operationId,

    required this.revision,

    required this.scanId,

    required this.collectorConfirmed,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'scan_id', required: true, includeIfNull: false)
  final String scanId;

  @JsonKey(name: r'collector_confirmed', required: true, includeIfNull: false)
  final bool collectorConfirmed;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilyGateCollection &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.scanId == scanId &&
          other.collectorConfirmed == collectorConfirmed;

  @override
  int get hashCode =>
      operationId.hashCode +
      revision.hashCode +
      scanId.hashCode +
      collectorConfirmed.hashCode;

  factory FamilyGateCollection.fromJson(Map<String, dynamic> json) =>
      _$FamilyGateCollectionFromJson(json);

  Map<String, dynamic> toJson() => _$FamilyGateCollectionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
