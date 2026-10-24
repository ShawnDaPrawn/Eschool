//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_transition_learner_choice.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_transition_search.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicTransitionSearch {
  /// Returns a new [AcademicTransitionSearch] instance.
  AcademicTransitionSearch({
    required this.page,

    required this.lastPage,

    required this.learners,
  });

  // minimum: 1
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  // minimum: 1
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  @JsonKey(name: r'learners', required: true, includeIfNull: false)
  final List<AcademicTransitionLearnerChoice> learners;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicTransitionSearch &&
          other.page == page &&
          other.lastPage == lastPage &&
          other.learners == learners;

  @override
  int get hashCode => page.hashCode + lastPage.hashCode + learners.hashCode;

  factory AcademicTransitionSearch.fromJson(Map<String, dynamic> json) =>
      _$AcademicTransitionSearchFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicTransitionSearchToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
