//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'foundation_eligibility_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FoundationEligibilityInput {
  /// Returns a new [FoundationEligibilityInput] instance.
  FoundationEligibilityInput({
    required this.operationId,

    required this.revision,

    required this.eligible,

    required this.reason,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'eligible', required: true, includeIfNull: false)
  final bool eligible;

  @JsonKey(name: r'reason', required: true, includeIfNull: false)
  final String reason;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FoundationEligibilityInput &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.eligible == eligible &&
          other.reason == reason;

  @override
  int get hashCode =>
      operationId.hashCode +
      revision.hashCode +
      eligible.hashCode +
      reason.hashCode;

  factory FoundationEligibilityInput.fromJson(Map<String, dynamic> json) =>
      _$FoundationEligibilityInputFromJson(json);

  Map<String, dynamic> toJson() => _$FoundationEligibilityInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
