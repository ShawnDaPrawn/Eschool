//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'reasoning_share_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ReasoningShareWrite {
  /// Returns a new [ReasoningShareWrite] instance.
  ReasoningShareWrite({
    required this.operationId,

    required this.learnerId,

    required this.text,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  /// The adjudicator's own words; they are responsible for what they share.
  @JsonKey(name: r'text', required: true, includeIfNull: false)
  final String text;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ReasoningShareWrite &&
          other.operationId == operationId &&
          other.learnerId == learnerId &&
          other.text == text;

  @override
  int get hashCode => operationId.hashCode + learnerId.hashCode + text.hashCode;

  factory ReasoningShareWrite.fromJson(Map<String, dynamic> json) =>
      _$ReasoningShareWriteFromJson(json);

  Map<String, dynamic> toJson() => _$ReasoningShareWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
