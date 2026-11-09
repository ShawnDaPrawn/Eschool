//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/strengths_shared_sections.dart';
import 'package:json_annotation/json_annotation.dart';

part 'strengths_snapshot.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class StrengthsSnapshot {
  /// Returns a new [StrengthsSnapshot] instance.
  StrengthsSnapshot({
    required this.learnerId,

    required this.firstName,

    required this.asOf,

    required this.sections,
  });

  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'first_name', required: true, includeIfNull: false)
  final String firstName;

  /// The day the copy was taken.
  @JsonKey(name: r'as_of', required: true, includeIfNull: false)
  final DateTime asOf;

  @JsonKey(name: r'sections', required: true, includeIfNull: false)
  final StrengthsSharedSections sections;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is StrengthsSnapshot &&
          other.learnerId == learnerId &&
          other.firstName == firstName &&
          other.asOf == asOf &&
          other.sections == sections;

  @override
  int get hashCode =>
      learnerId.hashCode +
      firstName.hashCode +
      asOf.hashCode +
      sections.hashCode;

  factory StrengthsSnapshot.fromJson(Map<String, dynamic> json) =>
      _$StrengthsSnapshotFromJson(json);

  Map<String, dynamic> toJson() => _$StrengthsSnapshotToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
