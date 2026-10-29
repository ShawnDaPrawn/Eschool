//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'wallet_payment_lookup_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WalletPaymentLookupWrite {
  /// Returns a new [WalletPaymentLookupWrite] instance.
  WalletPaymentLookupWrite({required this.qrToken});

  @JsonKey(name: r'qr_token', required: true, includeIfNull: false)
  final String qrToken;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WalletPaymentLookupWrite && other.qrToken == qrToken;

  @override
  int get hashCode => qrToken.hashCode;

  factory WalletPaymentLookupWrite.fromJson(Map<String, dynamic> json) =>
      _$WalletPaymentLookupWriteFromJson(json);

  Map<String, dynamic> toJson() => _$WalletPaymentLookupWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
