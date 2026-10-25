//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'intervention_owner_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class InterventionOwnerWrite {
  /// Returns a new [InterventionOwnerWrite] instance.
  InterventionOwnerWrite({
    required this.operationId,

    required this.expectedRevision,

    required this.ownerId,

    required this.reason,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'expected_revision', required: true, includeIfNull: false)
  final int expectedRevision;

  // minimum: 1
  @JsonKey(name: r'owner_id', required: true, includeIfNull: false)
  final int ownerId;

  @JsonKey(name: r'reason', required: true, includeIfNull: false)
  final String reason;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is InterventionOwnerWrite &&
          other.operationId == operationId &&
          other.expectedRevision == expectedRevision &&
          other.ownerId == ownerId &&
          other.reason == reason;

  @override
  int get hashCode =>
      operationId.hashCode +
      expectedRevision.hashCode +
      ownerId.hashCode +
      reason.hashCode;

  factory InterventionOwnerWrite.fromJson(Map<String, dynamic> json) =>
      _$InterventionOwnerWriteFromJson(json);

  Map<String, dynamic> toJson() => _$InterventionOwnerWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
