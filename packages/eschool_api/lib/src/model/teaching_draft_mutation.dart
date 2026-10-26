//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'teaching_draft_mutation.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TeachingDraftMutation {
  /// Returns a new [TeachingDraftMutation] instance.
  TeachingDraftMutation({
    required this.schoolId,

    required this.readerId,

    required this.id,

    required this.operationId,

    required this.kind,

    required this.state,

    required this.revision,

    required this.groupId,

    required this.contentVersionId,

    required this.learnerId,

    required this.taskId,

    required this.writeOperationId,
  });

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: TeachingDraftMutationKindEnum.unknownDefaultOpenApi,
  )
  final TeachingDraftMutationKindEnum kind;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: TeachingDraftMutationStateEnum.unknownDefaultOpenApi,
  )
  final TeachingDraftMutationStateEnum state;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  // minimum: 1
  @JsonKey(name: r'group_id', required: true, includeIfNull: false)
  final int groupId;

  // minimum: 1
  @JsonKey(name: r'content_version_id', required: true, includeIfNull: false)
  final int contentVersionId;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: true)
  final int? learnerId;

  // minimum: 1
  @JsonKey(name: r'task_id', required: true, includeIfNull: true)
  final int? taskId;

  @JsonKey(name: r'write_operation_id', required: true, includeIfNull: false)
  final String writeOperationId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TeachingDraftMutation &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.id == id &&
          other.operationId == operationId &&
          other.kind == kind &&
          other.state == state &&
          other.revision == revision &&
          other.groupId == groupId &&
          other.contentVersionId == contentVersionId &&
          other.learnerId == learnerId &&
          other.taskId == taskId &&
          other.writeOperationId == writeOperationId;

  @override
  int get hashCode =>
      schoolId.hashCode +
      readerId.hashCode +
      id.hashCode +
      operationId.hashCode +
      kind.hashCode +
      state.hashCode +
      revision.hashCode +
      groupId.hashCode +
      contentVersionId.hashCode +
      (learnerId == null ? 0 : learnerId.hashCode) +
      (taskId == null ? 0 : taskId.hashCode) +
      writeOperationId.hashCode;

  factory TeachingDraftMutation.fromJson(Map<String, dynamic> json) =>
      _$TeachingDraftMutationFromJson(json);

  Map<String, dynamic> toJson() => _$TeachingDraftMutationToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum TeachingDraftMutationKindEnum {
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

  const TeachingDraftMutationKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum TeachingDraftMutationStateEnum {
  @JsonValue(r'pending')
  pending(r'pending'),
  @JsonValue(r'ready')
  ready(r'ready'),
  @JsonValue(r'approved')
  approved(r'approved'),
  @JsonValue(r'discarded')
  discarded(r'discarded'),
  @JsonValue(r'published')
  published(r'published'),
  @JsonValue(r'applied')
  applied(r'applied'),
  @JsonValue(r'failed')
  failed(r'failed'),
  @JsonValue(r'quarantined')
  quarantined(r'quarantined'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const TeachingDraftMutationStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
