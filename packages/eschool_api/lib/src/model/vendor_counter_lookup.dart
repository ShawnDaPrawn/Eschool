//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'vendor_counter_lookup.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class VendorCounterLookup {
  /// Returns a new [VendorCounterLookup] instance.
  VendorCounterLookup({this.orderNumber, this.qrToken});

  @JsonKey(name: r'order_number', required: false, includeIfNull: false)
  final String? orderNumber;

  @JsonKey(name: r'qr_token', required: false, includeIfNull: false)
  final String? qrToken;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is VendorCounterLookup &&
          other.orderNumber == orderNumber &&
          other.qrToken == qrToken;

  @override
  int get hashCode =>
      (orderNumber == null ? 0 : orderNumber.hashCode) +
      (qrToken == null ? 0 : qrToken.hashCode);

  factory VendorCounterLookup.fromJson(Map<String, dynamic> json) =>
      _$VendorCounterLookupFromJson(json);

  Map<String, dynamic> toJson() => _$VendorCounterLookupToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
