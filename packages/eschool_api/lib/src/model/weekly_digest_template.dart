//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/weekly_digest_action_templates.dart';
import 'package:json_annotation/json_annotation.dart';

part 'weekly_digest_template.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WeeklyDigestTemplate {
  /// Returns a new [WeeklyDigestTemplate] instance.
  WeeklyDigestTemplate({
    required this.id,

    required this.language,

    required this.version,

    required this.revision,

    required this.title,

    required this.body,

    required this.actions,

    required this.noticePrompt,

    required this.approved,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'language', required: true, includeIfNull: false)
  final String language;

  // minimum: 1
  @JsonKey(name: r'version', required: true, includeIfNull: false)
  final int version;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'body', required: true, includeIfNull: false)
  final String body;

  @JsonKey(name: r'actions', required: true, includeIfNull: false)
  final WeeklyDigestActionTemplates actions;

  @JsonKey(name: r'notice_prompt', required: true, includeIfNull: false)
  final String noticePrompt;

  @JsonKey(name: r'approved', required: true, includeIfNull: false)
  final bool approved;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WeeklyDigestTemplate &&
          other.id == id &&
          other.language == language &&
          other.version == version &&
          other.revision == revision &&
          other.title == title &&
          other.body == body &&
          other.actions == actions &&
          other.noticePrompt == noticePrompt &&
          other.approved == approved;

  @override
  int get hashCode =>
      id.hashCode +
      language.hashCode +
      version.hashCode +
      revision.hashCode +
      title.hashCode +
      body.hashCode +
      actions.hashCode +
      noticePrompt.hashCode +
      approved.hashCode;

  factory WeeklyDigestTemplate.fromJson(Map<String, dynamic> json) =>
      _$WeeklyDigestTemplateFromJson(json);

  Map<String, dynamic> toJson() => _$WeeklyDigestTemplateToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
