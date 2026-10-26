//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'secure_encoding_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SecureEncodingReceipt {
  /// Returns a new [SecureEncodingReceipt] instance.
  SecureEncodingReceipt({
    required this.encoded,

    required this.planId,

    required this.cardId,

    required this.revision,

    required this.verifiedCounter,

    required this.encodedAt,

    required this.collectionAuthorized,

    required this.signInAuthorized,
  });

  @JsonKey(name: r'encoded', required: true, includeIfNull: false)
  final bool encoded;

  @JsonKey(name: r'plan_id', required: true, includeIfNull: false)
  final String planId;

  @JsonKey(name: r'card_id', required: true, includeIfNull: false)
  final String cardId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  // minimum: 0
  // maximum: 16777215
  @JsonKey(name: r'verified_counter', required: true, includeIfNull: false)
  final int verifiedCounter;

  @JsonKey(name: r'encoded_at', required: true, includeIfNull: false)
  final DateTime encodedAt;

  @JsonKey(name: r'collection_authorized', required: true, includeIfNull: false)
  final bool collectionAuthorized;

  @JsonKey(name: r'sign_in_authorized', required: true, includeIfNull: false)
  final bool signInAuthorized;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SecureEncodingReceipt &&
          other.encoded == encoded &&
          other.planId == planId &&
          other.cardId == cardId &&
          other.revision == revision &&
          other.verifiedCounter == verifiedCounter &&
          other.encodedAt == encodedAt &&
          other.collectionAuthorized == collectionAuthorized &&
          other.signInAuthorized == signInAuthorized;

  @override
  int get hashCode =>
      encoded.hashCode +
      planId.hashCode +
      cardId.hashCode +
      revision.hashCode +
      verifiedCounter.hashCode +
      encodedAt.hashCode +
      collectionAuthorized.hashCode +
      signInAuthorized.hashCode;

  factory SecureEncodingReceipt.fromJson(Map<String, dynamic> json) =>
      _$SecureEncodingReceiptFromJson(json);

  Map<String, dynamic> toJson() => _$SecureEncodingReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
