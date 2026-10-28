//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'vendor_counter_collect.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class VendorCounterCollect {
  /// Returns a new [VendorCounterCollect] instance.
  VendorCounterCollect({
    required this.operationId,

    this.orderNumber,

    this.qrToken,

    required this.confirmed,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'order_number', required: false, includeIfNull: false)
  final String? orderNumber;

  @JsonKey(name: r'qr_token', required: false, includeIfNull: false)
  final String? qrToken;

  /// Must be true. The server refuses unconfirmed handovers.
  @JsonKey(name: r'confirmed', required: true, includeIfNull: false)
  final bool confirmed;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is VendorCounterCollect &&
          other.operationId == operationId &&
          other.orderNumber == orderNumber &&
          other.qrToken == qrToken &&
          other.confirmed == confirmed;

  @override
  int get hashCode =>
      operationId.hashCode +
      (orderNumber == null ? 0 : orderNumber.hashCode) +
      (qrToken == null ? 0 : qrToken.hashCode) +
      confirmed.hashCode;

  factory VendorCounterCollect.fromJson(Map<String, dynamic> json) =>
      _$VendorCounterCollectFromJson(json);

  Map<String, dynamic> toJson() => _$VendorCounterCollectToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
