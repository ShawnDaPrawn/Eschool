//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'support_disclosure_history.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SupportDisclosureHistory {
  /// Returns a new [SupportDisclosureHistory] instance.
  SupportDisclosureHistory({
    required this.id,

    required this.revision,

    required this.userId,

    required this.teacherName,

    required this.expiresOn,

    required this.accommodations,

    required this.active,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  // minimum: 1
  @JsonKey(name: r'user_id', required: true, includeIfNull: false)
  final int userId;

  @JsonKey(name: r'teacher_name', required: true, includeIfNull: false)
  final String teacherName;

  @JsonKey(name: r'expires_on', required: true, includeIfNull: false)
  final String expiresOn;

  @JsonKey(name: r'accommodations', required: true, includeIfNull: false)
  final String accommodations;

  @JsonKey(name: r'active', required: true, includeIfNull: false)
  final bool active;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SupportDisclosureHistory &&
          other.id == id &&
          other.revision == revision &&
          other.userId == userId &&
          other.teacherName == teacherName &&
          other.expiresOn == expiresOn &&
          other.accommodations == accommodations &&
          other.active == active;

  @override
  int get hashCode =>
      id.hashCode +
      revision.hashCode +
      userId.hashCode +
      teacherName.hashCode +
      expiresOn.hashCode +
      accommodations.hashCode +
      active.hashCode;

  factory SupportDisclosureHistory.fromJson(Map<String, dynamic> json) =>
      _$SupportDisclosureHistoryFromJson(json);

  Map<String, dynamic> toJson() => _$SupportDisclosureHistoryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
