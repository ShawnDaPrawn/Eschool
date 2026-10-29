//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'wallet_counter_pay_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WalletCounterPayWrite {
  /// Returns a new [WalletCounterPayWrite] instance.
  WalletCounterPayWrite({
    required this.operationId,

    required this.intentId,

    this.qrToken,

    this.portraitRevision,

    this.portraitConfirmed,

    required this.confirmed,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'intent_id', required: true, includeIfNull: false)
  final String intentId;

  @JsonKey(name: r'qr_token', required: false, includeIfNull: false)
  final String? qrToken;

  // minimum: 1
  @JsonKey(name: r'portrait_revision', required: false, includeIfNull: false)
  final int? portraitRevision;

  @JsonKey(name: r'portrait_confirmed', required: false, includeIfNull: false)
  final bool? portraitConfirmed;

  @JsonKey(name: r'confirmed', required: true, includeIfNull: false)
  final bool confirmed;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WalletCounterPayWrite &&
          other.operationId == operationId &&
          other.intentId == intentId &&
          other.qrToken == qrToken &&
          other.portraitRevision == portraitRevision &&
          other.portraitConfirmed == portraitConfirmed &&
          other.confirmed == confirmed;

  @override
  int get hashCode =>
      operationId.hashCode +
      intentId.hashCode +
      (qrToken == null ? 0 : qrToken.hashCode) +
      (portraitRevision == null ? 0 : portraitRevision.hashCode) +
      (portraitConfirmed == null ? 0 : portraitConfirmed.hashCode) +
      confirmed.hashCode;

  factory WalletCounterPayWrite.fromJson(Map<String, dynamic> json) =>
      _$WalletCounterPayWriteFromJson(json);

  Map<String, dynamic> toJson() => _$WalletCounterPayWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
