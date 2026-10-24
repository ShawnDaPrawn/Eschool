//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_moderation_row.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicModerationRow {
  /// Returns a new [AcademicModerationRow] instance.
  AcademicModerationRow({
    required this.learnerId,

    required this.expectedRevision,
  });

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  // minimum: 1
  @JsonKey(name: r'expected_revision', required: true, includeIfNull: false)
  final int expectedRevision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicModerationRow &&
          other.learnerId == learnerId &&
          other.expectedRevision == expectedRevision;

  @override
  int get hashCode => learnerId.hashCode + expectedRevision.hashCode;

  factory AcademicModerationRow.fromJson(Map<String, dynamic> json) =>
      _$AcademicModerationRowFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicModerationRowToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
