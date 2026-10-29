//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/commerce_seller.dart';
import 'package:eschool_api/src/model/commerce_quote_line.dart';
import 'package:json_annotation/json_annotation.dart';

part 'wallet_card_payment_review.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WalletCardPaymentReview {
  /// Returns a new [WalletCardPaymentReview] instance.
  WalletCardPaymentReview({
    required this.schoolId,

    required this.actorUserId,

    required this.id,

    required this.vendorId,

    required this.kind,

    required this.currency,

    required this.amountCents,

    required this.netCents,

    required this.vatCents,

    required this.expiresAt,

    required this.seller,

    required this.lines,

    required this.holderName,

    required this.portraitRevision,
  });

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'actor_user_id', required: true, includeIfNull: false)
  final int actorUserId;

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'vendor_id', required: true, includeIfNull: false)
  final int vendorId;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: WalletCardPaymentReviewKindEnum.unknownDefaultOpenApi,
  )
  final WalletCardPaymentReviewKindEnum kind;

  @JsonKey(name: r'currency', required: true, includeIfNull: false)
  final String currency;

  // minimum: 0
  // maximum: 100000000000
  @JsonKey(name: r'amount_cents', required: true, includeIfNull: false)
  final int amountCents;

  // minimum: 0
  // maximum: 100000000000
  @JsonKey(name: r'net_cents', required: true, includeIfNull: false)
  final int netCents;

  // minimum: 0
  // maximum: 100000000000
  @JsonKey(name: r'vat_cents', required: true, includeIfNull: false)
  final int vatCents;

  @JsonKey(name: r'expires_at', required: true, includeIfNull: false)
  final DateTime expiresAt;

  @JsonKey(name: r'seller', required: true, includeIfNull: false)
  final CommerceSeller seller;

  @JsonKey(name: r'lines', required: true, includeIfNull: false)
  final List<CommerceQuoteLine> lines;

  @JsonKey(name: r'holder_name', required: true, includeIfNull: false)
  final String holderName;

  // minimum: 1
  @JsonKey(name: r'portrait_revision', required: true, includeIfNull: false)
  final int portraitRevision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WalletCardPaymentReview &&
          other.schoolId == schoolId &&
          other.actorUserId == actorUserId &&
          other.id == id &&
          other.vendorId == vendorId &&
          other.kind == kind &&
          other.currency == currency &&
          other.amountCents == amountCents &&
          other.netCents == netCents &&
          other.vatCents == vatCents &&
          other.expiresAt == expiresAt &&
          other.seller == seller &&
          other.lines == lines &&
          other.holderName == holderName &&
          other.portraitRevision == portraitRevision;

  @override
  int get hashCode =>
      schoolId.hashCode +
      actorUserId.hashCode +
      id.hashCode +
      vendorId.hashCode +
      kind.hashCode +
      currency.hashCode +
      amountCents.hashCode +
      netCents.hashCode +
      vatCents.hashCode +
      expiresAt.hashCode +
      seller.hashCode +
      lines.hashCode +
      holderName.hashCode +
      portraitRevision.hashCode;

  factory WalletCardPaymentReview.fromJson(Map<String, dynamic> json) =>
      _$WalletCardPaymentReviewFromJson(json);

  Map<String, dynamic> toJson() => _$WalletCardPaymentReviewToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum WalletCardPaymentReviewKindEnum {
  @JsonValue(r'qr')
  qr(r'qr'),
  @JsonValue(r'sun')
  sun(r'sun'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const WalletCardPaymentReviewKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
