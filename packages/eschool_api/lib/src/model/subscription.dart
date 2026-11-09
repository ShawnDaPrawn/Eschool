//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'subscription.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class Subscription {
  /// Returns a new [Subscription] instance.
  Subscription({
    required this.id,

    required this.reference,

    required this.plan,

    required this.status,

    required this.effectiveStatus,

    required this.learnerId,

    required this.priceCents,

    required this.currency,

    required this.periodMonths,

    required this.currentPeriodEnd,

    required this.requestedOn,

    required this.canCancel,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'reference', required: true, includeIfNull: false)
  final String reference;

  @JsonKey(
    name: r'plan',
    required: true,
    includeIfNull: false,
    unknownEnumValue: SubscriptionPlanEnum.unknownDefaultOpenApi,
  )
  final SubscriptionPlanEnum plan;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: SubscriptionStatusEnum.unknownDefaultOpenApi,
  )
  final SubscriptionStatusEnum status;

  @JsonKey(
    name: r'effective_status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: SubscriptionEffectiveStatusEnum.unknownDefaultOpenApi,
  )
  final SubscriptionEffectiveStatusEnum effectiveStatus;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: true)
  final int? learnerId;

  // minimum: 1
  @JsonKey(name: r'price_cents', required: true, includeIfNull: false)
  final int priceCents;

  @JsonKey(name: r'currency', required: true, includeIfNull: false)
  final String currency;

  // minimum: 1
  // maximum: 12
  @JsonKey(name: r'period_months', required: true, includeIfNull: false)
  final int periodMonths;

  @JsonKey(name: r'current_period_end', required: true, includeIfNull: true)
  final DateTime? currentPeriodEnd;

  @JsonKey(name: r'requested_on', required: true, includeIfNull: true)
  final DateTime? requestedOn;

  @JsonKey(name: r'can_cancel', required: true, includeIfNull: false)
  final bool canCancel;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is Subscription &&
          other.id == id &&
          other.reference == reference &&
          other.plan == plan &&
          other.status == status &&
          other.effectiveStatus == effectiveStatus &&
          other.learnerId == learnerId &&
          other.priceCents == priceCents &&
          other.currency == currency &&
          other.periodMonths == periodMonths &&
          other.currentPeriodEnd == currentPeriodEnd &&
          other.requestedOn == requestedOn &&
          other.canCancel == canCancel;

  @override
  int get hashCode =>
      id.hashCode +
      reference.hashCode +
      plan.hashCode +
      status.hashCode +
      effectiveStatus.hashCode +
      (learnerId == null ? 0 : learnerId.hashCode) +
      priceCents.hashCode +
      currency.hashCode +
      periodMonths.hashCode +
      (currentPeriodEnd == null ? 0 : currentPeriodEnd.hashCode) +
      (requestedOn == null ? 0 : requestedOn.hashCode) +
      canCancel.hashCode;

  factory Subscription.fromJson(Map<String, dynamic> json) =>
      _$SubscriptionFromJson(json);

  Map<String, dynamic> toJson() => _$SubscriptionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum SubscriptionPlanEnum {
  @JsonValue(r'per_child')
  perChild(r'per_child'),
  @JsonValue(r'family')
  family(r'family'),
  @JsonValue(r'school_licence')
  schoolLicence(r'school_licence'),
  @JsonValue(r'sponsored')
  sponsored(r'sponsored'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const SubscriptionPlanEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum SubscriptionStatusEnum {
  @JsonValue(r'requested')
  requested(r'requested'),
  @JsonValue(r'active')
  active(r'active'),
  @JsonValue(r'cancelled')
  cancelled(r'cancelled'),
  @JsonValue(r'ended')
  ended(r'ended'),
  @JsonValue(r'declined')
  declined(r'declined'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const SubscriptionStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum SubscriptionEffectiveStatusEnum {
  @JsonValue(r'requested')
  requested(r'requested'),
  @JsonValue(r'active')
  active(r'active'),
  @JsonValue(r'lapsed')
  lapsed(r'lapsed'),
  @JsonValue(r'cancelled')
  cancelled(r'cancelled'),
  @JsonValue(r'ended')
  ended(r'ended'),
  @JsonValue(r'declined')
  declined(r'declined'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const SubscriptionEffectiveStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
