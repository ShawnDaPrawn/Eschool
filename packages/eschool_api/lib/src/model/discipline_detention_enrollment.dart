//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'discipline_detention_enrollment.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DisciplineDetentionEnrollment {
  /// Returns a new [DisciplineDetentionEnrollment] instance.
  DisciplineDetentionEnrollment({
    required this.sessionId,

    required this.entryId,

    required this.sessionRevision,

    required this.entryRevision,
  });

  // minimum: 1
  @JsonKey(name: r'session_id', required: true, includeIfNull: false)
  final int sessionId;

  // minimum: 1
  @JsonKey(name: r'entry_id', required: true, includeIfNull: false)
  final int entryId;

  // minimum: 1
  @JsonKey(name: r'session_revision', required: true, includeIfNull: false)
  final int sessionRevision;

  // minimum: 1
  @JsonKey(name: r'entry_revision', required: true, includeIfNull: false)
  final int entryRevision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DisciplineDetentionEnrollment &&
          other.sessionId == sessionId &&
          other.entryId == entryId &&
          other.sessionRevision == sessionRevision &&
          other.entryRevision == entryRevision;

  @override
  int get hashCode =>
      sessionId.hashCode +
      entryId.hashCode +
      sessionRevision.hashCode +
      entryRevision.hashCode;

  factory DisciplineDetentionEnrollment.fromJson(Map<String, dynamic> json) =>
      _$DisciplineDetentionEnrollmentFromJson(json);

  Map<String, dynamic> toJson() => _$DisciplineDetentionEnrollmentToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
