//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'secure_card_sun_scan.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SecureCardSunScan {
  /// Returns a new [SecureCardSunScan] instance.
  SecureCardSunScan({
    required this.operationId,

    required this.cardId,

    required this.picc,

    required this.mac,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'card_id', required: true, includeIfNull: false)
  final String cardId;

  @JsonKey(name: r'picc', required: true, includeIfNull: false)
  final String picc;

  @JsonKey(name: r'mac', required: true, includeIfNull: false)
  final String mac;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SecureCardSunScan &&
          other.operationId == operationId &&
          other.cardId == cardId &&
          other.picc == picc &&
          other.mac == mac;

  @override
  int get hashCode =>
      operationId.hashCode + cardId.hashCode + picc.hashCode + mac.hashCode;

  factory SecureCardSunScan.fromJson(Map<String, dynamic> json) =>
      _$SecureCardSunScanFromJson(json);

  Map<String, dynamic> toJson() => _$SecureCardSunScanToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
