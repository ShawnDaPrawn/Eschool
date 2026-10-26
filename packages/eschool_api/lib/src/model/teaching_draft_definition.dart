//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/teaching_draft_section.dart';
import 'package:eschool_api/src/model/teaching_practice_question.dart';
import 'package:eschool_api/src/model/teaching_script_proposal.dart';
import 'package:json_annotation/json_annotation.dart';

part 'teaching_draft_definition.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TeachingDraftDefinition {
  /// Returns a new [TeachingDraftDefinition] instance.
  TeachingDraftDefinition({
    this.title,

    this.sections,

    this.text,

    this.questions,

    this.questionMarks,
  });

  @JsonKey(name: r'title', required: false, includeIfNull: false)
  final String? title;

  @JsonKey(name: r'sections', required: false, includeIfNull: false)
  final List<TeachingDraftSection>? sections;

  @JsonKey(name: r'text', required: false, includeIfNull: false)
  final String? text;

  @JsonKey(name: r'questions', required: false, includeIfNull: false)
  final List<TeachingPracticeQuestion>? questions;

  @JsonKey(name: r'question_marks', required: false, includeIfNull: false)
  final List<TeachingScriptProposal>? questionMarks;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TeachingDraftDefinition &&
          other.title == title &&
          other.sections == sections &&
          other.text == text &&
          other.questions == questions &&
          other.questionMarks == questionMarks;

  @override
  int get hashCode =>
      title.hashCode +
      sections.hashCode +
      text.hashCode +
      questions.hashCode +
      questionMarks.hashCode;

  factory TeachingDraftDefinition.fromJson(Map<String, dynamic> json) =>
      _$TeachingDraftDefinitionFromJson(json);

  Map<String, dynamic> toJson() => _$TeachingDraftDefinitionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
