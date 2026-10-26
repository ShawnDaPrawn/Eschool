//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'foundation_eligibility.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FoundationEligibility {
  /// Returns a new [FoundationEligibility] instance.
  FoundationEligibility({
    required this.teachingGroupId,

    required this.eligible,

    required this.revision,
  });

  // minimum: 1
  @JsonKey(name: r'teaching_group_id', required: true, includeIfNull: false)
  final int teachingGroupId;

  @JsonKey(name: r'eligible', required: true, includeIfNull: false)
  final bool eligible;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FoundationEligibility &&
          other.teachingGroupId == teachingGroupId &&
          other.eligible == eligible &&
          other.revision == revision;

  @override
  int get hashCode =>
      teachingGroupId.hashCode + eligible.hashCode + revision.hashCode;

  factory FoundationEligibility.fromJson(Map<String, dynamic> json) =>
      _$FoundationEligibilityFromJson(json);

  Map<String, dynamic> toJson() => _$FoundationEligibilityToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
