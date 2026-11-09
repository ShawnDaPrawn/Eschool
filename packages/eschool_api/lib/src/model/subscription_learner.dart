//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/subscription_child.dart';
import 'package:eschool_api/src/model/subscription_plan.dart';
import 'package:json_annotation/json_annotation.dart';

part 'subscription_learner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SubscriptionLearner {
  /// Returns a new [SubscriptionLearner] instance.
  SubscriptionLearner({
    required this.schoolId,

    required this.readerId,

    required this.required_,

    required this.pricingConfirmed,

    required this.currency,

    required this.plans,

    required this.paymentInstructions,

    required this.child,
  });

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  /// Whether this school asks for an entitlement for the tutor and the parent insights.
  @JsonKey(name: r'required', required: true, includeIfNull: false)
  final bool required_;

  /// False while the prices are placeholders awaiting the owner's decision.
  @JsonKey(name: r'pricing_confirmed', required: true, includeIfNull: false)
  final bool pricingConfirmed;

  @JsonKey(name: r'currency', required: true, includeIfNull: false)
  final String currency;

  @JsonKey(name: r'plans', required: true, includeIfNull: false)
  final List<SubscriptionPlan> plans;

  /// Navigate Ed's EFT details; quote the subscription reference.
  @JsonKey(name: r'payment_instructions', required: true, includeIfNull: false)
  final String paymentInstructions;

  @JsonKey(name: r'child', required: true, includeIfNull: false)
  final SubscriptionChild child;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SubscriptionLearner &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.required_ == required_ &&
          other.pricingConfirmed == pricingConfirmed &&
          other.currency == currency &&
          other.plans == plans &&
          other.paymentInstructions == paymentInstructions &&
          other.child == child;

  @override
  int get hashCode =>
      schoolId.hashCode +
      readerId.hashCode +
      required_.hashCode +
      pricingConfirmed.hashCode +
      currency.hashCode +
      plans.hashCode +
      paymentInstructions.hashCode +
      child.hashCode;

  factory SubscriptionLearner.fromJson(Map<String, dynamic> json) =>
      _$SubscriptionLearnerFromJson(json);

  Map<String, dynamic> toJson() => _$SubscriptionLearnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
