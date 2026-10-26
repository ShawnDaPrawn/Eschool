//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'teaching_draft_generation.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TeachingDraftGeneration {
  /// Returns a new [TeachingDraftGeneration] instance.
  TeachingDraftGeneration({
    required this.operationId,

    required this.contentVersionId,

    required this.kind,

    required this.language,

    required this.brief,

    required this.learnerId,

    required this.taskId,

    required this.photoIds,

    this.topicIds,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'content_version_id', required: true, includeIfNull: false)
  final int contentVersionId;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: TeachingDraftGenerationKindEnum.unknownDefaultOpenApi,
  )
  final TeachingDraftGenerationKindEnum kind;

  @JsonKey(
    name: r'language',
    required: true,
    includeIfNull: false,
    unknownEnumValue: TeachingDraftGenerationLanguageEnum.unknownDefaultOpenApi,
  )
  final TeachingDraftGenerationLanguageEnum language;

  @JsonKey(name: r'brief', required: true, includeIfNull: false)
  final String brief;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: true)
  final int? learnerId;

  // minimum: 1
  @JsonKey(name: r'task_id', required: true, includeIfNull: true)
  final int? taskId;

  @JsonKey(name: r'photo_ids', required: true, includeIfNull: false)
  final Set<String> photoIds;

  @JsonKey(name: r'topic_ids', required: false, includeIfNull: false)
  final Set<int>? topicIds;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TeachingDraftGeneration &&
          other.operationId == operationId &&
          other.contentVersionId == contentVersionId &&
          other.kind == kind &&
          other.language == language &&
          other.brief == brief &&
          other.learnerId == learnerId &&
          other.taskId == taskId &&
          other.photoIds == photoIds &&
          other.topicIds == topicIds;

  @override
  int get hashCode =>
      operationId.hashCode +
      contentVersionId.hashCode +
      kind.hashCode +
      language.hashCode +
      brief.hashCode +
      (learnerId == null ? 0 : learnerId.hashCode) +
      (taskId == null ? 0 : taskId.hashCode) +
      photoIds.hashCode +
      topicIds.hashCode;

  factory TeachingDraftGeneration.fromJson(Map<String, dynamic> json) =>
      _$TeachingDraftGenerationFromJson(json);

  Map<String, dynamic> toJson() => _$TeachingDraftGenerationToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum TeachingDraftGenerationKindEnum {
  @JsonValue(r'lesson')
  lesson(r'lesson'),
  @JsonValue(r'worksheet')
  worksheet(r'worksheet'),
  @JsonValue(r'practice')
  practice(r'practice'),
  @JsonValue(r'report_comment')
  reportComment(r'report_comment'),
  @JsonValue(r'script_marks')
  scriptMarks(r'script_marks'),
  @JsonValue(r'reteach')
  reteach(r'reteach'),
  @JsonValue(r'follow_up_quiz')
  followUpQuiz(r'follow_up_quiz'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const TeachingDraftGenerationKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum TeachingDraftGenerationLanguageEnum {
  @JsonValue(r'en')
  en(r'en'),
  @JsonValue(r'af')
  af(r'af'),
  @JsonValue(r'zu')
  zu(r'zu'),
  @JsonValue(r'ar')
  ar(r'ar'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const TeachingDraftGenerationLanguageEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
