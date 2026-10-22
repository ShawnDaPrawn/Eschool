//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'branding.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class Branding {
  /// Returns a new [Branding] instance.
  Branding({required this.themeColor, required this.secondaryColor});

  @JsonKey(name: r'theme_color', required: true, includeIfNull: true)
  final String? themeColor;

  @JsonKey(name: r'secondary_color', required: true, includeIfNull: true)
  final String? secondaryColor;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is Branding &&
          other.themeColor == themeColor &&
          other.secondaryColor == secondaryColor;

  @override
  int get hashCode =>
      (themeColor == null ? 0 : themeColor.hashCode) +
      (secondaryColor == null ? 0 : secondaryColor.hashCode);

  factory Branding.fromJson(Map<String, dynamic> json) =>
      _$BrandingFromJson(json);

  Map<String, dynamic> toJson() => _$BrandingToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
