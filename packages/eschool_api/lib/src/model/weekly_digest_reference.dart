//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'weekly_digest_reference.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WeeklyDigestReference {
  /// Returns a new [WeeklyDigestReference] instance.
  WeeklyDigestReference({required this.id, required this.revision});

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WeeklyDigestReference &&
          other.id == id &&
          other.revision == revision;

  @override
  int get hashCode => id.hashCode + revision.hashCode;

  factory WeeklyDigestReference.fromJson(Map<String, dynamic> json) =>
      _$WeeklyDigestReferenceFromJson(json);

  Map<String, dynamic> toJson() => _$WeeklyDigestReferenceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
