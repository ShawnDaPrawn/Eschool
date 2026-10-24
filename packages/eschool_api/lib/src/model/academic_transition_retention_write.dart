//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_transition_retention_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicTransitionRetentionWrite {
  /// Returns a new [AcademicTransitionRetentionWrite] instance.
  AcademicTransitionRetentionWrite({
    required this.days,

    required this.revision,
  });

  // minimum: 1
  // maximum: 30
  @JsonKey(name: r'days', required: true, includeIfNull: false)
  final int days;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicTransitionRetentionWrite &&
          other.days == days &&
          other.revision == revision;

  @override
  int get hashCode => days.hashCode + revision.hashCode;

  factory AcademicTransitionRetentionWrite.fromJson(
    Map<String, dynamic> json,
  ) => _$AcademicTransitionRetentionWriteFromJson(json);

  Map<String, dynamic> toJson() =>
      _$AcademicTransitionRetentionWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
