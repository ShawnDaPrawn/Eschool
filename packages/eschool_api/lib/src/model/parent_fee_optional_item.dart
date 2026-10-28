//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'parent_fee_optional_item.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ParentFeeOptionalItem {
  /// Returns a new [ParentFeeOptionalItem] instance.
  ParentFeeOptionalItem({
    required this.feeItemId,

    required this.name,

    required this.scope,

    required this.billing,

    required this.learnerId,

    required this.learnerName,

    required this.currency,

    required this.available,

    required this.canApprove,

    required this.priceCents,

    required this.shareCents,

    required this.vatCents,

    required this.selected,

    required this.currentTermsApproved,

    required this.revision,

    required this.fingerprint,
  });

  // minimum: 1
  @JsonKey(name: r'fee_item_id', required: true, includeIfNull: false)
  final int feeItemId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(
    name: r'scope',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ParentFeeOptionalItemScopeEnum.unknownDefaultOpenApi,
  )
  final ParentFeeOptionalItemScopeEnum scope;

  @JsonKey(
    name: r'billing',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ParentFeeOptionalItemBillingEnum.unknownDefaultOpenApi,
  )
  final ParentFeeOptionalItemBillingEnum billing;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: true)
  final int? learnerId;

  @JsonKey(name: r'learner_name', required: true, includeIfNull: true)
  final String? learnerName;

  @JsonKey(name: r'currency', required: true, includeIfNull: false)
  final String currency;

  @JsonKey(name: r'available', required: true, includeIfNull: false)
  final bool available;

  @JsonKey(name: r'can_approve', required: true, includeIfNull: false)
  final bool canApprove;

  // minimum: 0
  // maximum: 100000000000
  @JsonKey(name: r'price_cents', required: true, includeIfNull: true)
  final int? priceCents;

  // minimum: 0
  // maximum: 100000000000
  @JsonKey(name: r'share_cents', required: true, includeIfNull: true)
  final int? shareCents;

  // minimum: 0
  // maximum: 100000000000
  @JsonKey(name: r'vat_cents', required: true, includeIfNull: true)
  final int? vatCents;

  @JsonKey(name: r'selected', required: true, includeIfNull: false)
  final bool selected;

  @JsonKey(
    name: r'current_terms_approved',
    required: true,
    includeIfNull: false,
  )
  final bool currentTermsApproved;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'fingerprint', required: true, includeIfNull: true)
  final String? fingerprint;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ParentFeeOptionalItem &&
          other.feeItemId == feeItemId &&
          other.name == name &&
          other.scope == scope &&
          other.billing == billing &&
          other.learnerId == learnerId &&
          other.learnerName == learnerName &&
          other.currency == currency &&
          other.available == available &&
          other.canApprove == canApprove &&
          other.priceCents == priceCents &&
          other.shareCents == shareCents &&
          other.vatCents == vatCents &&
          other.selected == selected &&
          other.currentTermsApproved == currentTermsApproved &&
          other.revision == revision &&
          other.fingerprint == fingerprint;

  @override
  int get hashCode =>
      feeItemId.hashCode +
      name.hashCode +
      scope.hashCode +
      billing.hashCode +
      (learnerId == null ? 0 : learnerId.hashCode) +
      (learnerName == null ? 0 : learnerName.hashCode) +
      currency.hashCode +
      available.hashCode +
      canApprove.hashCode +
      (priceCents == null ? 0 : priceCents.hashCode) +
      (shareCents == null ? 0 : shareCents.hashCode) +
      (vatCents == null ? 0 : vatCents.hashCode) +
      selected.hashCode +
      currentTermsApproved.hashCode +
      revision.hashCode +
      (fingerprint == null ? 0 : fingerprint.hashCode);

  factory ParentFeeOptionalItem.fromJson(Map<String, dynamic> json) =>
      _$ParentFeeOptionalItemFromJson(json);

  Map<String, dynamic> toJson() => _$ParentFeeOptionalItemToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ParentFeeOptionalItemScopeEnum {
  @JsonValue(r'learner')
  learner(r'learner'),
  @JsonValue(r'family')
  family(r'family'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ParentFeeOptionalItemScopeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum ParentFeeOptionalItemBillingEnum {
  @JsonValue(r'once_per_year')
  oncePerYear(r'once_per_year'),
  @JsonValue(r'per_occurrence')
  perOccurrence(r'per_occurrence'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ParentFeeOptionalItemBillingEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
