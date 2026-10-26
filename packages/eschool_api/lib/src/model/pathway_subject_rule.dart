//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'pathway_subject_rule.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PathwaySubjectRule {
  /// Returns a new [PathwaySubjectRule] instance.
  PathwaySubjectRule({
    required this.subjectId,

    required this.minimumBp,

    required this.countsForScore,
  });

  // minimum: 1
  @JsonKey(name: r'subject_id', required: true, includeIfNull: false)
  final int subjectId;

  // minimum: 0
  // maximum: 10000
  @JsonKey(name: r'minimum_bp', required: true, includeIfNull: true)
  final int? minimumBp;

  @JsonKey(name: r'counts_for_score', required: true, includeIfNull: false)
  final bool countsForScore;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PathwaySubjectRule &&
          other.subjectId == subjectId &&
          other.minimumBp == minimumBp &&
          other.countsForScore == countsForScore;

  @override
  int get hashCode =>
      subjectId.hashCode +
      (minimumBp == null ? 0 : minimumBp.hashCode) +
      countsForScore.hashCode;

  factory PathwaySubjectRule.fromJson(Map<String, dynamic> json) =>
      _$PathwaySubjectRuleFromJson(json);

  Map<String, dynamic> toJson() => _$PathwaySubjectRuleToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
