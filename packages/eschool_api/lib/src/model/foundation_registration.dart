//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'foundation_registration.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FoundationRegistration {
  /// Returns a new [FoundationRegistration] instance.
  FoundationRegistration({
    required this.id,

    required this.tabletAccessCode,

    required this.kind,

    required this.expiresAt,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  /// Private classroom-only tablet access code. Keep in memory and never send in a URL, cookie or an ordinary authentication request.
  @JsonKey(name: r'tablet_access_code', required: true, includeIfNull: false)
  final String tabletAccessCode;

  @JsonKey(name: r'kind', required: true, includeIfNull: false)
  final String kind;

  @JsonKey(name: r'expires_at', required: true, includeIfNull: false)
  final DateTime expiresAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FoundationRegistration &&
          other.id == id &&
          other.tabletAccessCode == tabletAccessCode &&
          other.kind == kind &&
          other.expiresAt == expiresAt;

  @override
  int get hashCode =>
      id.hashCode +
      tabletAccessCode.hashCode +
      kind.hashCode +
      expiresAt.hashCode;

  factory FoundationRegistration.fromJson(Map<String, dynamic> json) =>
      _$FoundationRegistrationFromJson(json);

  Map<String, dynamic> toJson() => _$FoundationRegistrationToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
