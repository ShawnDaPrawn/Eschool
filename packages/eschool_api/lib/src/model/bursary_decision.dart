//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'bursary_decision.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class BursaryDecision {
  /// Returns a new [BursaryDecision] instance.
  BursaryDecision({
    required this.id,

    required this.state,

    required this.revision,

    required this.feeDiscountId,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @JsonKey(name: r'state', required: true, includeIfNull: false)
  final String state;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'fee_discount_id', required: true, includeIfNull: true)
  final int? feeDiscountId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is BursaryDecision &&
          other.id == id &&
          other.state == state &&
          other.revision == revision &&
          other.feeDiscountId == feeDiscountId;

  @override
  int get hashCode =>
      id.hashCode +
      state.hashCode +
      revision.hashCode +
      (feeDiscountId == null ? 0 : feeDiscountId.hashCode);

  factory BursaryDecision.fromJson(Map<String, dynamic> json) =>
      _$BursaryDecisionFromJson(json);

  Map<String, dynamic> toJson() => _$BursaryDecisionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
