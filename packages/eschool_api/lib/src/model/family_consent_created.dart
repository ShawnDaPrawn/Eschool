//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'family_consent_created.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FamilyConsentCreated {
  /// Returns a new [FamilyConsentCreated] instance.
  FamilyConsentCreated({
    required this.id,

    required this.fingerprint,

    required this.revision,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @JsonKey(name: r'fingerprint', required: true, includeIfNull: false)
  final String fingerprint;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilyConsentCreated &&
          other.id == id &&
          other.fingerprint == fingerprint &&
          other.revision == revision;

  @override
  int get hashCode => id.hashCode + fingerprint.hashCode + revision.hashCode;

  factory FamilyConsentCreated.fromJson(Map<String, dynamic> json) =>
      _$FamilyConsentCreatedFromJson(json);

  Map<String, dynamic> toJson() => _$FamilyConsentCreatedToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
