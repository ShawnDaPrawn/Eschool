//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'family_consent_cancelled.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FamilyConsentCancelled {
  /// Returns a new [FamilyConsentCancelled] instance.
  FamilyConsentCancelled({
    required this.id,

    required this.status,

    required this.revision,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @JsonKey(name: r'status', required: true, includeIfNull: false)
  final String status;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilyConsentCancelled &&
          other.id == id &&
          other.status == status &&
          other.revision == revision;

  @override
  int get hashCode => id.hashCode + status.hashCode + revision.hashCode;

  factory FamilyConsentCancelled.fromJson(Map<String, dynamic> json) =>
      _$FamilyConsentCancelledFromJson(json);

  Map<String, dynamic> toJson() => _$FamilyConsentCancelledToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
