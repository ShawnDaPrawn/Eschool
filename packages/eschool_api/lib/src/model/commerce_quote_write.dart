//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/commerce_cart_line.dart';
import 'package:json_annotation/json_annotation.dart';

part 'commerce_quote_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommerceQuoteWrite {
  /// Returns a new [CommerceQuoteWrite] instance.
  CommerceQuoteWrite({
    required this.vendorId,

    required this.walletId,

    this.learnerId,

    required this.lines,
  });

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'vendor_id', required: true, includeIfNull: false)
  final int vendorId;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'wallet_id', required: true, includeIfNull: false)
  final int walletId;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'learner_id', required: false, includeIfNull: false)
  final int? learnerId;

  @JsonKey(name: r'lines', required: true, includeIfNull: false)
  final List<CommerceCartLine> lines;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommerceQuoteWrite &&
          other.vendorId == vendorId &&
          other.walletId == walletId &&
          other.learnerId == learnerId &&
          other.lines == lines;

  @override
  int get hashCode =>
      vendorId.hashCode +
      walletId.hashCode +
      (learnerId == null ? 0 : learnerId.hashCode) +
      lines.hashCode;

  factory CommerceQuoteWrite.fromJson(Map<String, dynamic> json) =>
      _$CommerceQuoteWriteFromJson(json);

  Map<String, dynamic> toJson() => _$CommerceQuoteWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
