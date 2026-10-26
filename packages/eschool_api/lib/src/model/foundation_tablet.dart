//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'foundation_tablet.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FoundationTablet {
  /// Returns a new [FoundationTablet] instance.
  FoundationTablet({
    required this.id,

    required this.name,

    required this.status,

    required this.expiresAt,

    required this.active,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'status', required: true, includeIfNull: false)
  final String status;

  @JsonKey(name: r'expires_at', required: true, includeIfNull: false)
  final DateTime expiresAt;

  @JsonKey(name: r'active', required: true, includeIfNull: false)
  final bool active;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FoundationTablet &&
          other.id == id &&
          other.name == name &&
          other.status == status &&
          other.expiresAt == expiresAt &&
          other.active == active;

  @override
  int get hashCode =>
      id.hashCode +
      name.hashCode +
      status.hashCode +
      expiresAt.hashCode +
      active.hashCode;

  factory FoundationTablet.fromJson(Map<String, dynamic> json) =>
      _$FoundationTabletFromJson(json);

  Map<String, dynamic> toJson() => _$FoundationTabletToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
