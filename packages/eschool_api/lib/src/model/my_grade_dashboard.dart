//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/my_grade_learner.dart';
import 'package:eschool_api/src/model/my_grade_discipline_queue.dart';
import 'package:eschool_api/src/model/my_grade_actions.dart';
import 'package:eschool_api/src/model/my_grade_awards.dart';
import 'package:json_annotation/json_annotation.dart';

part 'my_grade_dashboard.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class MyGradeDashboard {
  /// Returns a new [MyGradeDashboard] instance.
  MyGradeDashboard({
    required this.schoolId,

    required this.readerId,

    required this.sessionYearId,

    required this.gradeLevelId,

    required this.onDate,

    required this.writable,

    required this.page,

    required this.lastPage,

    required this.total,

    required this.riskAvailable,

    required this.learners,

    required this.discipline,

    required this.awards,

    required this.actions,
  });

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  // minimum: 1
  @JsonKey(name: r'grade_level_id', required: true, includeIfNull: false)
  final int gradeLevelId;

  @JsonKey(name: r'on_date', required: true, includeIfNull: false)
  final String onDate;

  @JsonKey(name: r'writable', required: true, includeIfNull: false)
  final bool writable;

  // minimum: 1
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  // minimum: 1
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  // minimum: 0
  @JsonKey(name: r'total', required: true, includeIfNull: false)
  final int total;

  @JsonKey(name: r'risk_available', required: true, includeIfNull: false)
  final bool riskAvailable;

  @JsonKey(name: r'learners', required: true, includeIfNull: false)
  final List<MyGradeLearner> learners;

  @JsonKey(name: r'discipline', required: true, includeIfNull: false)
  final MyGradeDisciplineQueue discipline;

  @JsonKey(name: r'awards', required: true, includeIfNull: false)
  final MyGradeAwards awards;

  @JsonKey(name: r'actions', required: true, includeIfNull: false)
  final MyGradeActions actions;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is MyGradeDashboard &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.sessionYearId == sessionYearId &&
          other.gradeLevelId == gradeLevelId &&
          other.onDate == onDate &&
          other.writable == writable &&
          other.page == page &&
          other.lastPage == lastPage &&
          other.total == total &&
          other.riskAvailable == riskAvailable &&
          other.learners == learners &&
          other.discipline == discipline &&
          other.awards == awards &&
          other.actions == actions;

  @override
  int get hashCode =>
      schoolId.hashCode +
      readerId.hashCode +
      sessionYearId.hashCode +
      gradeLevelId.hashCode +
      onDate.hashCode +
      writable.hashCode +
      page.hashCode +
      lastPage.hashCode +
      total.hashCode +
      riskAvailable.hashCode +
      learners.hashCode +
      discipline.hashCode +
      awards.hashCode +
      actions.hashCode;

  factory MyGradeDashboard.fromJson(Map<String, dynamic> json) =>
      _$MyGradeDashboardFromJson(json);

  Map<String, dynamic> toJson() => _$MyGradeDashboardToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
