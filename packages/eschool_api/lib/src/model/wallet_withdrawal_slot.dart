//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'wallet_withdrawal_slot.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WalletWithdrawalSlot {
  /// Returns a new [WalletWithdrawalSlot] instance.
  WalletWithdrawalSlot({
    required this.id,

    required this.startsAt,

    required this.endsAt,

    required this.label,

    required this.availablePlaces,
  });

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'starts_at', required: true, includeIfNull: false)
  final DateTime startsAt;

  @JsonKey(name: r'ends_at', required: true, includeIfNull: false)
  final DateTime endsAt;

  @JsonKey(name: r'label', required: true, includeIfNull: false)
  final String label;

  // minimum: 1
  // maximum: 1000
  @JsonKey(name: r'available_places', required: true, includeIfNull: false)
  final int availablePlaces;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WalletWithdrawalSlot &&
          other.id == id &&
          other.startsAt == startsAt &&
          other.endsAt == endsAt &&
          other.label == label &&
          other.availablePlaces == availablePlaces;

  @override
  int get hashCode =>
      id.hashCode +
      startsAt.hashCode +
      endsAt.hashCode +
      label.hashCode +
      availablePlaces.hashCode;

  factory WalletWithdrawalSlot.fromJson(Map<String, dynamic> json) =>
      _$WalletWithdrawalSlotFromJson(json);

  Map<String, dynamic> toJson() => _$WalletWithdrawalSlotToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
