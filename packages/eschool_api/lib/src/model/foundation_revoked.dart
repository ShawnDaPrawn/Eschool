//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'foundation_revoked.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FoundationRevoked {
  /// Returns a new [FoundationRevoked] instance.
  FoundationRevoked({required this.revoked});

  @JsonKey(name: r'revoked', required: true, includeIfNull: false)
  final bool revoked;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FoundationRevoked && other.revoked == revoked;

  @override
  int get hashCode => revoked.hashCode;

  factory FoundationRevoked.fromJson(Map<String, dynamic> json) =>
      _$FoundationRevokedFromJson(json);

  Map<String, dynamic> toJson() => _$FoundationRevokedToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
