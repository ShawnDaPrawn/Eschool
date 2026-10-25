//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'support_disclosure_summary.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SupportDisclosureSummary {
  /// Returns a new [SupportDisclosureSummary] instance.
  SupportDisclosureSummary({
    required this.id,

    required this.revision,

    required this.userId,

    required this.teacherName,

    required this.expiresOn,

    required this.accommodations,
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

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SupportDisclosureSummary &&
          other.id == id &&
          other.revision == revision &&
          other.userId == userId &&
          other.teacherName == teacherName &&
          other.expiresOn == expiresOn &&
          other.accommodations == accommodations;

  @override
  int get hashCode =>
      id.hashCode +
      revision.hashCode +
      userId.hashCode +
      teacherName.hashCode +
      expiresOn.hashCode +
      accommodations.hashCode;

  factory SupportDisclosureSummary.fromJson(Map<String, dynamic> json) =>
      _$SupportDisclosureSummaryFromJson(json);

  Map<String, dynamic> toJson() => _$SupportDisclosureSummaryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
