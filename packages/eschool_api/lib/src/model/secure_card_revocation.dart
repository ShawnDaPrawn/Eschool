//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'secure_card_revocation.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SecureCardRevocation {
  /// Returns a new [SecureCardRevocation] instance.
  SecureCardRevocation({required this.revoked});

  @JsonKey(name: r'revoked', required: true, includeIfNull: false)
  final bool revoked;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SecureCardRevocation && other.revoked == revoked;

  @override
  int get hashCode => revoked.hashCode;

  factory SecureCardRevocation.fromJson(Map<String, dynamic> json) =>
      _$SecureCardRevocationFromJson(json);

  Map<String, dynamic> toJson() => _$SecureCardRevocationToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
