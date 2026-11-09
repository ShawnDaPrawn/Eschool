//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/learning_path_family_summary_subjects_inner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'learning_path_family_summary.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearningPathFamilySummary {
  /// Returns a new [LearningPathFamilySummary] instance.
  LearningPathFamilySummary({
    required this.schoolId,

    required this.readerId,

    required this.learnerId,

    required this.subjects,

    required this.suggestion,
  });

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'subjects', required: true, includeIfNull: false)
  final List<LearningPathFamilySummarySubjectsInner> subjects;

  @JsonKey(name: r'suggestion', required: true, includeIfNull: false)
  final String suggestion;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearningPathFamilySummary &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.learnerId == learnerId &&
          other.subjects == subjects &&
          other.suggestion == suggestion;

  @override
  int get hashCode =>
      schoolId.hashCode +
      readerId.hashCode +
      learnerId.hashCode +
      subjects.hashCode +
      suggestion.hashCode;

  factory LearningPathFamilySummary.fromJson(Map<String, dynamic> json) =>
      _$LearningPathFamilySummaryFromJson(json);

  Map<String, dynamic> toJson() => _$LearningPathFamilySummaryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
