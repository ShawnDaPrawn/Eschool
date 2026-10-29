//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'vendor_operator_counter.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class VendorOperatorCounter {
  /// Returns a new [VendorOperatorCounter] instance.
  VendorOperatorCounter({
    required this.id,

    required this.name,

    required this.currency,

    required this.canCollect,
  });

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'currency', required: true, includeIfNull: false)
  final String currency;

  @JsonKey(name: r'can_collect', required: true, includeIfNull: false)
  final bool canCollect;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is VendorOperatorCounter &&
          other.id == id &&
          other.name == name &&
          other.currency == currency &&
          other.canCollect == canCollect;

  @override
  int get hashCode =>
      id.hashCode + name.hashCode + currency.hashCode + canCollect.hashCode;

  factory VendorOperatorCounter.fromJson(Map<String, dynamic> json) =>
      _$VendorOperatorCounterFromJson(json);

  Map<String, dynamic> toJson() => _$VendorOperatorCounterToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
