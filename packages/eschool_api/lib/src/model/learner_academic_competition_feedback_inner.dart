//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'learner_academic_competition_feedback_inner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearnerAcademicCompetitionFeedbackInner {
  /// Returns a new [LearnerAcademicCompetitionFeedbackInner] instance.
  LearnerAcademicCompetitionFeedbackInner({
    required this.text,

    required this.sharedOn,
  });

  @JsonKey(name: r'text', required: true, includeIfNull: false)
  final String text;

  @JsonKey(name: r'shared_on', required: true, includeIfNull: false)
  final String sharedOn;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearnerAcademicCompetitionFeedbackInner &&
          other.text == text &&
          other.sharedOn == sharedOn;

  @override
  int get hashCode => text.hashCode + sharedOn.hashCode;

  factory LearnerAcademicCompetitionFeedbackInner.fromJson(
    Map<String, dynamic> json,
  ) => _$LearnerAcademicCompetitionFeedbackInnerFromJson(json);

  Map<String, dynamic> toJson() =>
      _$LearnerAcademicCompetitionFeedbackInnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
