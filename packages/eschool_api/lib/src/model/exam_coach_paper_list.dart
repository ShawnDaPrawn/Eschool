//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/exam_coach_paper_summary.dart';
import 'package:json_annotation/json_annotation.dart';

part 'exam_coach_paper_list.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ExamCoachPaperList {
  /// Returns a new [ExamCoachPaperList] instance.
  ExamCoachPaperList({
    required this.groupId,

    required this.papers,

    required this.page,

    required this.hasMore,
  });

  // minimum: 1
  @JsonKey(name: r'group_id', required: true, includeIfNull: false)
  final int groupId;

  @JsonKey(name: r'papers', required: true, includeIfNull: false)
  final List<ExamCoachPaperSummary> papers;

  // minimum: 1
  // maximum: 1000
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  @JsonKey(name: r'has_more', required: true, includeIfNull: false)
  final bool hasMore;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ExamCoachPaperList &&
          other.groupId == groupId &&
          other.papers == papers &&
          other.page == page &&
          other.hasMore == hasMore;

  @override
  int get hashCode =>
      groupId.hashCode + papers.hashCode + page.hashCode + hasMore.hashCode;

  factory ExamCoachPaperList.fromJson(Map<String, dynamic> json) =>
      _$ExamCoachPaperListFromJson(json);

  Map<String, dynamic> toJson() => _$ExamCoachPaperListToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
