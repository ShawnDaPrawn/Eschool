//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'secure_offline_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SecureOfflineReceipt {
  /// Returns a new [SecureOfflineReceipt] instance.
  SecureOfflineReceipt({
    required this.scanId,

    required this.cardId,

    required this.outcome,

    required this.collectionAuthorized,

    required this.signInAuthorized,
  });

  @JsonKey(name: r'scan_id', required: true, includeIfNull: false)
  final String scanId;

  @JsonKey(name: r'card_id', required: true, includeIfNull: false)
  final String cardId;

  @JsonKey(name: r'outcome', required: true, includeIfNull: false)
  final String outcome;

  /// Always false. History reconciliation cannot authorize collection.
  @JsonKey(name: r'collection_authorized', required: true, includeIfNull: false)
  final bool collectionAuthorized;

  /// Always false. No user or classroom session is created.
  @JsonKey(name: r'sign_in_authorized', required: true, includeIfNull: false)
  final bool signInAuthorized;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SecureOfflineReceipt &&
          other.scanId == scanId &&
          other.cardId == cardId &&
          other.outcome == outcome &&
          other.collectionAuthorized == collectionAuthorized &&
          other.signInAuthorized == signInAuthorized;

  @override
  int get hashCode =>
      scanId.hashCode +
      cardId.hashCode +
      outcome.hashCode +
      collectionAuthorized.hashCode +
      signInAuthorized.hashCode;

  factory SecureOfflineReceipt.fromJson(Map<String, dynamic> json) =>
      _$SecureOfflineReceiptFromJson(json);

  Map<String, dynamic> toJson() => _$SecureOfflineReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
