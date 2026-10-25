//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'support_disclose_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SupportDiscloseInput {
  /// Returns a new [SupportDiscloseInput] instance.
  SupportDiscloseInput({
    required this.operationId,

    required this.revision,

    required this.reason,

    required this.userId,

    required this.expiresOn,

    required this.accommodations,

    required this.confirmation,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'reason', required: true, includeIfNull: false)
  final String reason;

  // minimum: 1
  @JsonKey(name: r'user_id', required: true, includeIfNull: false)
  final int userId;

  @JsonKey(name: r'expires_on', required: true, includeIfNull: false)
  final String expiresOn;

  @JsonKey(name: r'accommodations', required: true, includeIfNull: false)
  final String accommodations;

  /// Must be true: explicit approval of these instructions for the named teacher.
  @JsonKey(name: r'confirmation', required: true, includeIfNull: false)
  final bool confirmation;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SupportDiscloseInput &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.reason == reason &&
          other.userId == userId &&
          other.expiresOn == expiresOn &&
          other.accommodations == accommodations &&
          other.confirmation == confirmation;

  @override
  int get hashCode =>
      operationId.hashCode +
      revision.hashCode +
      reason.hashCode +
      userId.hashCode +
      expiresOn.hashCode +
      accommodations.hashCode +
      confirmation.hashCode;

  factory SupportDiscloseInput.fromJson(Map<String, dynamic> json) =>
      _$SupportDiscloseInputFromJson(json);

  Map<String, dynamic> toJson() => _$SupportDiscloseInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
