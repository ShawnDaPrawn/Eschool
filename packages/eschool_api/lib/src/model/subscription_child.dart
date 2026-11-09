//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/subscription.dart';
import 'package:eschool_api/src/model/subscription_entitlement.dart';
import 'package:json_annotation/json_annotation.dart';

part 'subscription_child.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SubscriptionChild {
  /// Returns a new [SubscriptionChild] instance.
  SubscriptionChild({
    required this.learnerId,

    required this.name,

    required this.approved,

    required this.entitlement,

    required this.nextPlan,

    required this.subscription,
  });

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'approved', required: true, includeIfNull: false)
  final bool approved;

  @JsonKey(name: r'entitlement', required: true, includeIfNull: false)
  final SubscriptionEntitlement entitlement;

  @JsonKey(
    name: r'next_plan',
    required: true,
    includeIfNull: false,
    unknownEnumValue: SubscriptionChildNextPlanEnum.unknownDefaultOpenApi,
  )
  final SubscriptionChildNextPlanEnum nextPlan;

  @JsonKey(name: r'subscription', required: true, includeIfNull: true)
  final Subscription? subscription;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SubscriptionChild &&
          other.learnerId == learnerId &&
          other.name == name &&
          other.approved == approved &&
          other.entitlement == entitlement &&
          other.nextPlan == nextPlan &&
          other.subscription == subscription;

  @override
  int get hashCode =>
      learnerId.hashCode +
      name.hashCode +
      approved.hashCode +
      entitlement.hashCode +
      nextPlan.hashCode +
      (subscription == null ? 0 : subscription.hashCode);

  factory SubscriptionChild.fromJson(Map<String, dynamic> json) =>
      _$SubscriptionChildFromJson(json);

  Map<String, dynamic> toJson() => _$SubscriptionChildToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum SubscriptionChildNextPlanEnum {
  @JsonValue(r'per_child')
  perChild(r'per_child'),
  @JsonValue(r'family')
  family(r'family'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const SubscriptionChildNextPlanEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
