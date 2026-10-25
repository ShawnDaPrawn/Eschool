//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'support_reason_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SupportReasonInput {
  /// Returns a new [SupportReasonInput] instance.
  SupportReasonInput({
    required this.operationId,

    required this.revision,

    required this.reason,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'reason', required: true, includeIfNull: false)
  final String reason;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SupportReasonInput &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.reason == reason;

  @override
  int get hashCode =>
      operationId.hashCode + revision.hashCode + reason.hashCode;

  factory SupportReasonInput.fromJson(Map<String, dynamic> json) =>
      _$SupportReasonInputFromJson(json);

  Map<String, dynamic> toJson() => _$SupportReasonInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
