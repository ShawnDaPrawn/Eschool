//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'subscription_plan.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SubscriptionPlan {
  /// Returns a new [SubscriptionPlan] instance.
  SubscriptionPlan({
    required this.plan,

    required this.priceCents,

    required this.periodMonths,
  });

  @JsonKey(
    name: r'plan',
    required: true,
    includeIfNull: false,
    unknownEnumValue: SubscriptionPlanPlanEnum.unknownDefaultOpenApi,
  )
  final SubscriptionPlanPlanEnum plan;

  // minimum: 1
  @JsonKey(name: r'price_cents', required: true, includeIfNull: false)
  final int priceCents;

  // minimum: 1
  // maximum: 12
  @JsonKey(name: r'period_months', required: true, includeIfNull: false)
  final int periodMonths;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SubscriptionPlan &&
          other.plan == plan &&
          other.priceCents == priceCents &&
          other.periodMonths == periodMonths;

  @override
  int get hashCode =>
      plan.hashCode + priceCents.hashCode + periodMonths.hashCode;

  factory SubscriptionPlan.fromJson(Map<String, dynamic> json) =>
      _$SubscriptionPlanFromJson(json);

  Map<String, dynamic> toJson() => _$SubscriptionPlanToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum SubscriptionPlanPlanEnum {
  @JsonValue(r'per_child')
  perChild(r'per_child'),
  @JsonValue(r'family')
  family(r'family'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const SubscriptionPlanPlanEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
