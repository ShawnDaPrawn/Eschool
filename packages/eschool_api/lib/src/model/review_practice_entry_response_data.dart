// Generated union compatibility for dart-dio 7.25.0.
import 'package:eschool_api/src/model/practice_review.dart';
import 'package:eschool_api/src/model/practice_review_v2.dart';
import 'package:eschool_api/src/model/practice_schedule.dart';
import 'package:eschool_api/src/model/practice_solution.dart';

/// One complete practice response variant; never a combination of both.
class ReviewPracticeEntryResponseData {
  const ReviewPracticeEntryResponseData._({this.legacy, this.v2});

  final PracticeReview? legacy;
  final PracticeReviewV2? v2;

  factory ReviewPracticeEntryResponseData.fromJson(Map<String, dynamic> json) =>
      json.containsKey('state')
      ? ReviewPracticeEntryResponseData._(v2: PracticeReviewV2.fromJson(json))
      : ReviewPracticeEntryResponseData._(
          legacy: PracticeReview.fromJson(json),
        );

  int get id => v2?.id ?? legacy!.id;
  int get entryId => v2?.entryId ?? legacy!.entryId;
  String get reviewedOn => v2?.reviewedOn ?? legacy!.reviewedOn;
  int? get streakDays => v2 != null ? v2!.streakDays : legacy!.streakDays;
  bool? get correct => v2 != null ? v2!.correct : legacy!.correct;
  PracticeSchedule? get schedule =>
      v2 != null ? v2!.schedule : legacy!.schedule;
  PracticeReviewV2StateEnum? get state => v2?.state;
  int? get achievedUnits => v2?.achievedUnits;
  int? get maximumUnits => v2?.maximumUnits;
  Map<String, bool>? get parts => v2?.parts;
  PracticeReviewV2MarkedHowEnum? get markedHow => v2?.markedHow;
  PracticeSolution? get solution => v2?.solution;

  Map<String, dynamic> toJson() => v2?.toJson() ?? legacy!.toJson();
}
