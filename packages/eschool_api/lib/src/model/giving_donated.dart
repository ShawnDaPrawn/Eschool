//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'giving_donated.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GivingDonated {
  /// Returns a new [GivingDonated] instance.
  GivingDonated({
    required this.id,

    required this.receipt,

    required this.ledgerEntryId,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'receipt', required: true, includeIfNull: false)
  final String receipt;

  // minimum: 1
  @JsonKey(name: r'ledger_entry_id', required: true, includeIfNull: false)
  final int ledgerEntryId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GivingDonated &&
          other.id == id &&
          other.receipt == receipt &&
          other.ledgerEntryId == ledgerEntryId;

  @override
  int get hashCode => id.hashCode + receipt.hashCode + ledgerEntryId.hashCode;

  factory GivingDonated.fromJson(Map<String, dynamic> json) =>
      _$GivingDonatedFromJson(json);

  Map<String, dynamic> toJson() => _$GivingDonatedToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
