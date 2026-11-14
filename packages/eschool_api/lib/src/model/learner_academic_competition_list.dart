//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/learner_academic_competition.dart';
import 'package:json_annotation/json_annotation.dart';

part 'learner_academic_competition_list.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearnerAcademicCompetitionList {
  /// Returns a new [LearnerAcademicCompetitionList] instance.
  LearnerAcademicCompetitionList({required this.items});

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<LearnerAcademicCompetition> items;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearnerAcademicCompetitionList && other.items == items;

  @override
  int get hashCode => items.hashCode;

  factory LearnerAcademicCompetitionList.fromJson(Map<String, dynamic> json) =>
      _$LearnerAcademicCompetitionListFromJson(json);

  Map<String, dynamic> toJson() => _$LearnerAcademicCompetitionListToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
