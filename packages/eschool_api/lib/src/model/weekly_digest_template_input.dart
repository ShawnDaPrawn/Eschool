//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/weekly_digest_action_templates.dart';
import 'package:json_annotation/json_annotation.dart';

part 'weekly_digest_template_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WeeklyDigestTemplateInput {
  /// Returns a new [WeeklyDigestTemplateInput] instance.
  WeeklyDigestTemplateInput({
    required this.operationId,

    required this.language,

    required this.title,

    required this.body,

    required this.actions,

    required this.noticePrompt,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'language', required: true, includeIfNull: false)
  final String language;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'body', required: true, includeIfNull: false)
  final String body;

  @JsonKey(name: r'actions', required: true, includeIfNull: false)
  final WeeklyDigestActionTemplates actions;

  @JsonKey(name: r'notice_prompt', required: true, includeIfNull: false)
  final String noticePrompt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WeeklyDigestTemplateInput &&
          other.operationId == operationId &&
          other.language == language &&
          other.title == title &&
          other.body == body &&
          other.actions == actions &&
          other.noticePrompt == noticePrompt;

  @override
  int get hashCode =>
      operationId.hashCode +
      language.hashCode +
      title.hashCode +
      body.hashCode +
      actions.hashCode +
      noticePrompt.hashCode;

  factory WeeklyDigestTemplateInput.fromJson(Map<String, dynamic> json) =>
      _$WeeklyDigestTemplateInputFromJson(json);

  Map<String, dynamic> toJson() => _$WeeklyDigestTemplateInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
