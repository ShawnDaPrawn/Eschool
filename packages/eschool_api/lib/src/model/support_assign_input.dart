//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'support_assign_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SupportAssignInput {
  /// Returns a new [SupportAssignInput] instance.
  SupportAssignInput({
    required this.operationId,

    required this.reason,

    required this.learnerId,

    required this.userId,

    required this.startsOn,

    required this.endsOn,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'reason', required: true, includeIfNull: false)
  final String reason;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  // minimum: 1
  @JsonKey(name: r'user_id', required: true, includeIfNull: false)
  final int userId;

  @JsonKey(name: r'starts_on', required: true, includeIfNull: false)
  final String startsOn;

  @JsonKey(name: r'ends_on', required: true, includeIfNull: false)
  final String endsOn;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SupportAssignInput &&
          other.operationId == operationId &&
          other.reason == reason &&
          other.learnerId == learnerId &&
          other.userId == userId &&
          other.startsOn == startsOn &&
          other.endsOn == endsOn;

  @override
  int get hashCode =>
      operationId.hashCode +
      reason.hashCode +
      learnerId.hashCode +
      userId.hashCode +
      startsOn.hashCode +
      endsOn.hashCode;

  factory SupportAssignInput.fromJson(Map<String, dynamic> json) =>
      _$SupportAssignInputFromJson(json);

  Map<String, dynamic> toJson() => _$SupportAssignInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
