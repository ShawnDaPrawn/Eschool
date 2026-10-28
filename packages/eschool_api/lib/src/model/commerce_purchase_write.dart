//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'commerce_purchase_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommercePurchaseWrite {
  /// Returns a new [CommercePurchaseWrite] instance.
  CommercePurchaseWrite({
    required this.operationId,

    required this.quote,

    required this.confirmed,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'quote', required: true, includeIfNull: false)
  final String quote;

  /// Must be true after reviewing the quoted original wallet, products, quantities, tax and total.
  @JsonKey(name: r'confirmed', required: true, includeIfNull: false)
  final bool confirmed;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommercePurchaseWrite &&
          other.operationId == operationId &&
          other.quote == quote &&
          other.confirmed == confirmed;

  @override
  int get hashCode =>
      operationId.hashCode + quote.hashCode + confirmed.hashCode;

  factory CommercePurchaseWrite.fromJson(Map<String, dynamic> json) =>
      _$CommercePurchaseWriteFromJson(json);

  Map<String, dynamic> toJson() => _$CommercePurchaseWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
