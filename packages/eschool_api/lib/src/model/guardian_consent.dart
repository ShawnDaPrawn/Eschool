//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'guardian_consent.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GuardianConsent {
  /// Returns a new [GuardianConsent] instance.
  GuardianConsent({required this.version, required this.text});

  @JsonKey(name: r'version', required: true, includeIfNull: false)
  final String version;

  @JsonKey(name: r'text', required: true, includeIfNull: false)
  final String text;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GuardianConsent &&
          other.version == version &&
          other.text == text;

  @override
  int get hashCode => version.hashCode + text.hashCode;

  factory GuardianConsent.fromJson(Map<String, dynamic> json) =>
      _$GuardianConsentFromJson(json);

  Map<String, dynamic> toJson() => _$GuardianConsentToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
