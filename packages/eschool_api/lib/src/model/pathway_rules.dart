//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/pathway_subject_rule.dart';
import 'package:eschool_api/src/model/pathway_elective_group.dart';
import 'package:eschool_api/src/model/pathway_score_band.dart';
import 'package:json_annotation/json_annotation.dart';

part 'pathway_rules.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PathwayRules {
  /// Returns a new [PathwayRules] instance.
  PathwayRules({
    this.subjects,

    this.bands,

    this.bestN,

    this.minimumScore,

    this.compulsorySubjectIds,

    this.electiveGroups,
  });

  @JsonKey(name: r'subjects', required: false, includeIfNull: false)
  final List<PathwaySubjectRule>? subjects;

  @JsonKey(name: r'bands', required: false, includeIfNull: false)
  final List<PathwayScoreBand>? bands;

  // minimum: 1
  // maximum: 20
  @JsonKey(name: r'best_n', required: false, includeIfNull: false)
  final int? bestN;

  // minimum: 0
  // maximum: 400
  @JsonKey(name: r'minimum_score', required: false, includeIfNull: false)
  final int? minimumScore;

  @JsonKey(
    name: r'compulsory_subject_ids',
    required: false,
    includeIfNull: false,
  )
  final List<int>? compulsorySubjectIds;

  @JsonKey(name: r'elective_groups', required: false, includeIfNull: false)
  final List<PathwayElectiveGroup>? electiveGroups;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PathwayRules &&
          other.subjects == subjects &&
          other.bands == bands &&
          other.bestN == bestN &&
          other.minimumScore == minimumScore &&
          other.compulsorySubjectIds == compulsorySubjectIds &&
          other.electiveGroups == electiveGroups;

  @override
  int get hashCode =>
      subjects.hashCode +
      bands.hashCode +
      bestN.hashCode +
      minimumScore.hashCode +
      compulsorySubjectIds.hashCode +
      electiveGroups.hashCode;

  factory PathwayRules.fromJson(Map<String, dynamic> json) =>
      _$PathwayRulesFromJson(json);

  Map<String, dynamic> toJson() => _$PathwayRulesToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
