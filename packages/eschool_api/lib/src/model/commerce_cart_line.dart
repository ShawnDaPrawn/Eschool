//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'commerce_cart_line.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommerceCartLine {
  /// Returns a new [CommerceCartLine] instance.
  CommerceCartLine({required this.productId, required this.quantity});

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'product_id', required: true, includeIfNull: false)
  final int productId;

  // minimum: 1
  // maximum: 999
  @JsonKey(name: r'quantity', required: true, includeIfNull: false)
  final int quantity;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommerceCartLine &&
          other.productId == productId &&
          other.quantity == quantity;

  @override
  int get hashCode => productId.hashCode + quantity.hashCode;

  factory CommerceCartLine.fromJson(Map<String, dynamic> json) =>
      _$CommerceCartLineFromJson(json);

  Map<String, dynamic> toJson() => _$CommerceCartLineToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
