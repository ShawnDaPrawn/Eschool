//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'secure_card_scan_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SecureCardScanReceipt {
  /// Returns a new [SecureCardScanReceipt] instance.
  SecureCardScanReceipt({
    required this.scanId,

    required this.cardId,

    required this.personId,

    required this.type,

    required this.outcome,

    required this.scannedAt,
  });

  @JsonKey(name: r'scan_id', required: true, includeIfNull: false)
  final String scanId;

  @JsonKey(name: r'card_id', required: true, includeIfNull: false)
  final String cardId;

  // minimum: 1
  @JsonKey(name: r'person_id', required: true, includeIfNull: false)
  final int personId;

  @JsonKey(name: r'type', required: true, includeIfNull: false)
  final String type;

  @JsonKey(name: r'outcome', required: true, includeIfNull: false)
  final String outcome;

  @JsonKey(name: r'scanned_at', required: true, includeIfNull: false)
  final DateTime scannedAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SecureCardScanReceipt &&
          other.scanId == scanId &&
          other.cardId == cardId &&
          other.personId == personId &&
          other.type == type &&
          other.outcome == outcome &&
          other.scannedAt == scannedAt;

  @override
  int get hashCode =>
      scanId.hashCode +
      cardId.hashCode +
      personId.hashCode +
      type.hashCode +
      outcome.hashCode +
      scannedAt.hashCode;

  factory SecureCardScanReceipt.fromJson(Map<String, dynamic> json) =>
      _$SecureCardScanReceiptFromJson(json);

  Map<String, dynamic> toJson() => _$SecureCardScanReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
