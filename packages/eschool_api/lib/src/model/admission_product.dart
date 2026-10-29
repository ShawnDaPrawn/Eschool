//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'admission_product.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AdmissionProduct {
  /// Returns a new [AdmissionProduct] instance.
  AdmissionProduct({
    required this.typeId,

    required this.eventId,

    required this.eventName,

    required this.kind,

    required this.transferRule,

    required this.remainingCapacity,

    required this.quantityLimit,

    required this.canPurchase,

    required this.startsAt,

    required this.endsAt,
  });

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'type_id', required: true, includeIfNull: false)
  final int typeId;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'event_id', required: true, includeIfNull: false)
  final int eventId;

  @JsonKey(name: r'event_name', required: true, includeIfNull: false)
  final String eventName;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: AdmissionProductKindEnum.unknownDefaultOpenApi,
  )
  final AdmissionProductKindEnum kind;

  @JsonKey(
    name: r'transfer_rule',
    required: true,
    includeIfNull: false,
    unknownEnumValue: AdmissionProductTransferRuleEnum.unknownDefaultOpenApi,
  )
  final AdmissionProductTransferRuleEnum transferRule;

  // minimum: 0
  // maximum: 1000000
  @JsonKey(name: r'remaining_capacity', required: true, includeIfNull: false)
  final int remainingCapacity;

  // minimum: 0
  // maximum: 100
  @JsonKey(name: r'quantity_limit', required: true, includeIfNull: false)
  final int quantityLimit;

  @JsonKey(name: r'can_purchase', required: true, includeIfNull: false)
  final bool canPurchase;

  @JsonKey(name: r'starts_at', required: true, includeIfNull: false)
  final DateTime startsAt;

  @JsonKey(name: r'ends_at', required: true, includeIfNull: false)
  final DateTime endsAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AdmissionProduct &&
          other.typeId == typeId &&
          other.eventId == eventId &&
          other.eventName == eventName &&
          other.kind == kind &&
          other.transferRule == transferRule &&
          other.remainingCapacity == remainingCapacity &&
          other.quantityLimit == quantityLimit &&
          other.canPurchase == canPurchase &&
          other.startsAt == startsAt &&
          other.endsAt == endsAt;

  @override
  int get hashCode =>
      typeId.hashCode +
      eventId.hashCode +
      eventName.hashCode +
      kind.hashCode +
      transferRule.hashCode +
      remainingCapacity.hashCode +
      quantityLimit.hashCode +
      canPurchase.hashCode +
      startsAt.hashCode +
      endsAt.hashCode;

  factory AdmissionProduct.fromJson(Map<String, dynamic> json) =>
      _$AdmissionProductFromJson(json);

  Map<String, dynamic> toJson() => _$AdmissionProductToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum AdmissionProductKindEnum {
  @JsonValue(r'general')
  general(r'general'),
  @JsonValue(r'named')
  named(r'named'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const AdmissionProductKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum AdmissionProductTransferRuleEnum {
  @JsonValue(r'none')
  none(r'none'),
  @JsonValue(r'families')
  families(r'families'),
  @JsonValue(r'wallet_profiles')
  walletProfiles(r'wallet_profiles'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const AdmissionProductTransferRuleEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
