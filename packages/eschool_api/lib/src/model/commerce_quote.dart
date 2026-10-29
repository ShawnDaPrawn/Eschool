//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/commerce_seller.dart';
import 'package:eschool_api/src/model/commerce_quote_line.dart';
import 'package:json_annotation/json_annotation.dart';

part 'commerce_quote.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommerceQuote {
  /// Returns a new [CommerceQuote] instance.
  CommerceQuote({
    required this.schoolId,

    required this.actorUserId,

    required this.walletId,

    required this.walletOwnerId,

    required this.learnerId,

    required this.vendorId,

    required this.currency,

    required this.amountCents,

    required this.netCents,

    required this.vatCents,

    required this.seller,

    required this.lines,

    required this.quote,

    required this.expiresAt,
  });

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'actor_user_id', required: true, includeIfNull: false)
  final int actorUserId;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'wallet_id', required: true, includeIfNull: false)
  final int walletId;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'wallet_owner_id', required: true, includeIfNull: false)
  final int walletOwnerId;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'learner_id', required: true, includeIfNull: true)
  final int? learnerId;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'vendor_id', required: true, includeIfNull: false)
  final int vendorId;

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

  @JsonKey(name: r'seller', required: true, includeIfNull: false)
  final CommerceSeller seller;

  @JsonKey(name: r'lines', required: true, includeIfNull: false)
  final List<CommerceQuoteLine> lines;

  @JsonKey(name: r'quote', required: true, includeIfNull: false)
  final String quote;

  @JsonKey(name: r'expires_at', required: true, includeIfNull: false)
  final DateTime expiresAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommerceQuote &&
          other.schoolId == schoolId &&
          other.actorUserId == actorUserId &&
          other.walletId == walletId &&
          other.walletOwnerId == walletOwnerId &&
          other.learnerId == learnerId &&
          other.vendorId == vendorId &&
          other.currency == currency &&
          other.amountCents == amountCents &&
          other.netCents == netCents &&
          other.vatCents == vatCents &&
          other.seller == seller &&
          other.lines == lines &&
          other.quote == quote &&
          other.expiresAt == expiresAt;

  @override
  int get hashCode =>
      schoolId.hashCode +
      actorUserId.hashCode +
      walletId.hashCode +
      walletOwnerId.hashCode +
      (learnerId == null ? 0 : learnerId.hashCode) +
      vendorId.hashCode +
      currency.hashCode +
      amountCents.hashCode +
      netCents.hashCode +
      vatCents.hashCode +
      seller.hashCode +
      lines.hashCode +
      quote.hashCode +
      expiresAt.hashCode;

  factory CommerceQuote.fromJson(Map<String, dynamic> json) =>
      _$CommerceQuoteFromJson(json);

  Map<String, dynamic> toJson() => _$CommerceQuoteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
