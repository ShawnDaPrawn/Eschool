//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'giving_pledged.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GivingPledged {
  /// Returns a new [GivingPledged] instance.
  GivingPledged({required this.id, required this.reference});

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'reference', required: true, includeIfNull: false)
  final String reference;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GivingPledged && other.id == id && other.reference == reference;

  @override
  int get hashCode => id.hashCode + reference.hashCode;

  factory GivingPledged.fromJson(Map<String, dynamic> json) =>
      _$GivingPledgedFromJson(json);

  Map<String, dynamic> toJson() => _$GivingPledgedToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
