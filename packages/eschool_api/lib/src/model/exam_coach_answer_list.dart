//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/exam_coach_answer_summary.dart';
import 'package:json_annotation/json_annotation.dart';

part 'exam_coach_answer_list.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ExamCoachAnswerList {
  /// Returns a new [ExamCoachAnswerList] instance.
  ExamCoachAnswerList({
    required this.answers,

    required this.page,

    required this.hasMore,
  });

  @JsonKey(name: r'answers', required: true, includeIfNull: false)
  final List<ExamCoachAnswerSummary> answers;

  // minimum: 1
  // maximum: 1000
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  @JsonKey(name: r'has_more', required: true, includeIfNull: false)
  final bool hasMore;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ExamCoachAnswerList &&
          other.answers == answers &&
          other.page == page &&
          other.hasMore == hasMore;

  @override
  int get hashCode => answers.hashCode + page.hashCode + hasMore.hashCode;

  factory ExamCoachAnswerList.fromJson(Map<String, dynamic> json) =>
      _$ExamCoachAnswerListFromJson(json);

  Map<String, dynamic> toJson() => _$ExamCoachAnswerListToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
