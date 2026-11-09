//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'open_badge_credential_issuer.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class OpenBadgeCredentialIssuer {
  /// Returns a new [OpenBadgeCredentialIssuer] instance.
  OpenBadgeCredentialIssuer({
    required this.id,

    required this.type,

    required this.name,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @JsonKey(name: r'type', required: true, includeIfNull: false)
  final List<String> type;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is OpenBadgeCredentialIssuer &&
          other.id == id &&
          other.type == type &&
          other.name == name;

  @override
  int get hashCode => id.hashCode + type.hashCode + name.hashCode;

  factory OpenBadgeCredentialIssuer.fromJson(Map<String, dynamic> json) =>
      _$OpenBadgeCredentialIssuerFromJson(json);

  Map<String, dynamic> toJson() => _$OpenBadgeCredentialIssuerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
